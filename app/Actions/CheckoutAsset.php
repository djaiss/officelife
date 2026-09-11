<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AssetAssigneeTypeEnum;
use App\Enums\AssetConditionEnum;
use App\Enums\OccurrenceTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Employee;
use App\Models\Office;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Hand a piece of equipment to somebody, an office, or another piece of
 * equipment. This is an operation rather than a field being set, and the two
 * checks at the top of it are the ones that get skipped.
 */
class CheckoutAsset
{
    private AssetAssignment $assignment;

    public function __construct(
        private readonly User $author,
        private readonly Asset $asset,
        private readonly Model $assignee,
        private readonly ?Carbon $expectedReturnAt = null,
        private readonly ?AssetConditionEnum $condition = null,
        private ?string $notes = null,
        private readonly ?Office $office = null,
    ) {}

    public function execute(): AssetAssignment
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->checkout();
        $this->publish();
        $this->log();

        return $this->assignment;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::AssetCheckout)
            ->forCompany($this->asset->company)
            ->authorize();
    }

    private function sanitize(): void
    {
        $this->notes = TextSanitizer::nullablePlainText($this->notes);
    }

    private function validate(): void
    {
        if ($this->asset->isArchived()) {
            throw new InvalidArgumentException('That piece of equipment has left the fleet');
        }

        if (! $this->asset->status->isDeployable()) {
            throw new InvalidArgumentException('That piece of equipment is not ready to be handed out');
        }

        if ($this->asset->isAssigned()) {
            throw new InvalidArgumentException('Somebody already has that piece of equipment');
        }

        $this->validateAssignee();

        if ($this->office !== null && $this->office->company_id !== $this->asset->company_id) {
            throw new InvalidArgumentException('The office belongs to another company');
        }
    }

    private function validateAssignee(): void
    {
        if (AssetAssigneeTypeEnum::forModel($this->assignee) === null) {
            throw new InvalidArgumentException('Equipment cannot be handed to a '.$this->assignee::class);
        }

        $companyId = match (true) {
            $this->assignee instanceof Employee => $this->assignee->company_id,
            $this->assignee instanceof Office => $this->assignee->company_id,
            $this->assignee instanceof Asset => $this->assignee->company_id,
            default => null,
        };

        if ($companyId !== $this->asset->company_id) {
            throw new InvalidArgumentException('Whoever is being handed the equipment belongs to another company');
        }

        if ($this->assignee instanceof Asset) {
            $this->refuseLoop();
        }
    }

    private function refuseLoop(): void
    {
        $holder = $this->assignee;

        while ($holder instanceof Asset) {
            if ($holder->id === $this->asset->id) {
                throw new InvalidArgumentException('That would leave the equipment holding itself');
            }

            $assignment = $holder->activeAssignment()
                ->where('assignee_type', AssetAssigneeTypeEnum::Asset)
                ->first();

            if ($assignment === null) {
                return;
            }

            $holder = Asset::query()->find($assignment->assignee_id);
        }
    }

    private function checkout(): void
    {
        DB::transaction(function (): void {
            $this->assignment = AssetAssignment::query()->create([
                'asset_id' => $this->asset->id,
                'assignee_type' => AssetAssigneeTypeEnum::forModel($this->assignee),
                'assignee_id' => $this->assignee->getKey(),
                'assigned_by_user_id' => $this->author->id,
                'assigned_at' => now(),
                'expected_return_at' => $this->expectedReturnAt,
                'condition_at_checkout' => $this->condition,
                'checkout_notes' => $this->notes,
            ]);

            // Nowhere yet to read the office of a colleague from, so saying
            // nothing leaves the equipment where it was.
            if ($this->office !== null) {
                $this->asset->current_office_id = $this->office->id;
                $this->asset->save();
            }
        });
    }

    private function publish(): void
    {
        new PublishOccurrence(
            type: OccurrenceTypeEnum::AssetCheckedOut,
            company: $this->asset->company,
            subject: $this->asset,
            actor: $this->author,
            payload: [
                'tag' => $this->asset->asset_tag,
                'assignee_type' => $this->assignment->assignee_type->value,
                'assignee_id' => $this->assignment->assignee_id,
            ],
        )->execute();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->asset->company,
            user: $this->author,
            action: UserActionEnum::AssetCheckedOut,
            parameters: [
                'tag' => $this->asset->asset_tag,
                'assignee' => $this->assigneeName(),
            ],
        )->onQueue('low');
    }

    private function assigneeName(): string
    {
        return match (true) {
            $this->assignee instanceof Employee => $this->assignee->name,
            $this->assignee instanceof Office => $this->assignee->name,
            $this->assignee instanceof Asset => $this->assignee->asset_tag,
            default => '',
        };
    }
}
