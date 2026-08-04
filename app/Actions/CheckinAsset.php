<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AssetConditionEnum;
use App\Enums\OccurrenceTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetStatus;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Take a piece of equipment back.
 *
 * The assignment is closed rather than deleted, so who had it and what state it
 * was in each time is still there to read.
 *
 * A status may be given, and only then is one written: something that comes back
 * damaged goes to Awaiting repair. Nothing is set automatically, because
 * checkout never changed the status and there is nothing to put back.
 */
class CheckinAsset
{
    private AssetAssignment $assignment;

    public function __construct(
        private readonly User $author,
        private readonly Asset $asset,
        private readonly ?AssetConditionEnum $condition = null,
        private ?string $notes = null,
        private readonly ?Location $location = null,
        private readonly ?AssetStatus $status = null,
    ) {}

    public function execute(): AssetAssignment
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->checkin();
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
        $assignment = $this->asset->activeAssignment()->first();

        if ($assignment === null) {
            throw new InvalidArgumentException('Nobody has that piece of equipment');
        }

        $this->assignment = $assignment;

        if ($this->location !== null && $this->location->company_id !== $this->asset->company_id) {
            throw new InvalidArgumentException('The office belongs to another company');
        }

        if ($this->status !== null && $this->status->company_id !== null && $this->status->company_id !== $this->asset->company_id) {
            throw new InvalidArgumentException('The status belongs to another company');
        }
    }

    private function checkin(): void
    {
        DB::transaction(function (): void {
            $this->assignment->returned_at = now();
            $this->assignment->condition_at_checkin = $this->condition;
            $this->assignment->checkin_notes = $this->notes;
            $this->assignment->returned_to_location_id = $this->location?->id;
            $this->assignment->save();

            if ($this->location !== null) {
                $this->asset->current_location_id = $this->location->id;
            }

            if ($this->status !== null) {
                $this->asset->status_id = $this->status->id;
            }

            $this->asset->save();
        });
    }

    private function publish(): void
    {
        new PublishOccurrence(
            type: OccurrenceTypeEnum::AssetCheckedIn,
            company: $this->asset->company,
            subject: $this->asset,
            actor: $this->author,
            payload: [
                'tag' => $this->asset->asset_tag,
                'condition' => $this->condition?->value,
            ],
        )->execute();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->asset->company,
            user: $this->author,
            action: UserActionEnum::AssetCheckin,
            parameters: ['tag' => $this->asset->asset_tag],
        )->onQueue('low');
    }
}
