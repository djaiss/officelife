<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Asset;
use App\Models\User;
use InvalidArgumentException;

/**
 * Remove a piece of equipment from the records altogether. Only ever right for
 * something entered by mistake.
 *
 * Refused once anybody has ever held it. Deleting then would erase who had what
 * and when, which is most of what the module is for. Archiving is what to do
 * with equipment that has left the fleet.
 */
class DestroyAsset
{
    public function __construct(
        private readonly User $author,
        private readonly Asset $asset,
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
            ->forCompany($this->asset->company)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->asset->assignments()->exists()) {
            throw new InvalidArgumentException('Somebody has held that piece of equipment, so archive it rather than deleting it');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->asset->company,
            user: $this->author,
            action: UserActionEnum::AssetDeletion,
            parameters: ['tag' => $this->asset->asset_tag],
        )->onQueue('low');
    }

    private function destroy(): void
    {
        $this->asset->delete();
    }
}
