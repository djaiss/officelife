<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AssetStatusTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\AssetStatus;
use App\Models\Company;
use App\Models\User;
use InvalidArgumentException;

/**
 * Add a status of its own to a company. In transit, Awaiting wipe, Pending
 * disposal: real states no product should ship in advance.
 *
 * The company names it and says which of the four types it behaves as, and that
 * is all the code needs to treat it correctly. It never gets a key, which is
 * reserved for the handful of system statuses the code recognises by name.
 */
class CreateAssetStatus
{
    private AssetStatus $status;

    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private string $name,
        private readonly AssetStatusTypeEnum $type,
        private ?string $color = null,
    ) {}

    public function execute(): AssetStatus
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->status;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::AssetManage)
            ->forCompany($this->company)
            ->authorize();
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->color = TextSanitizer::nullablePlainText($this->color);
    }

    /**
     * The name has to be clear of the statuses every company gets as well as of
     * the ones this company added, since both appear in the same list.
     */
    private function validate(): void
    {
        if ($this->name === '') {
            throw new InvalidArgumentException('A status needs a name');
        }

        // The statuses we ship hold a translation key rather than a name, so the
        // clash has to be looked for against what each one currently reads as.
        $taken = AssetStatus::query()
            ->where(function ($query): void {
                $query->where('company_id', $this->company->id)->orWhereNull('company_id');
            })
            ->get()
            ->contains(fn (AssetStatus $status): bool => $status->name === $this->name);

        if ($taken) {
            throw new InvalidArgumentException('There is already a status called '.$this->name);
        }
    }

    private function create(): void
    {
        $this->status = AssetStatus::query()->create([
            'company_id' => $this->company->id,
            'key' => null,
            'name' => $this->name,
            'name_translation_key' => null,
            'type' => $this->type,
            'color' => $this->color,
            'is_system' => false,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::AssetStatusCreation,
            parameters: ['name' => $this->status->name],
        )->onQueue('low');
    }
}
