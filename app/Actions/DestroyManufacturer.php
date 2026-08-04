<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Manufacturer;
use App\Models\User;
use InvalidArgumentException;

/**
 * Remove a manufacturer from the catalogue. Refused while a model still points
 * at it, since every asset of that model would be left saying it was made by
 * nobody.
 */
class DestroyManufacturer
{
    public function __construct(
        private readonly User $author,
        private readonly Manufacturer $manufacturer,
    ) {}

    public function execute(): bool
    {
        $this->authorize();
        $this->validate();
        $this->log();
        $this->destroy();

        return true;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::AssetManage)
            ->forCompany($this->manufacturer->company)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->manufacturer->assetModels()->exists()) {
            throw new InvalidArgumentException('The manufacturer still makes equipment the company owns');
        }
    }

    /**
     * Logged before the row goes, so the name is still there to write down.
     */
    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->manufacturer->company,
            user: $this->author,
            action: UserActionEnum::ManufacturerDeletion,
            parameters: ['name' => $this->manufacturer->name],
        )->onQueue('low');
    }

    private function destroy(): void
    {
        $this->manufacturer->delete();
    }
}
