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
 * Take a piece of equipment out of the fleet, keeping everything recorded about
 * it and everybody who has held it.
 *
 * Refused while somebody still has it. Equipment that is out with a colleague
 * has not left the fleet, it is with somebody, and archiving it would leave an
 * assignment nobody would ever close.
 */
class ArchiveAsset
{
    public function __construct(
        private readonly User $author,
        private readonly Asset $asset,
    ) {}

    public function execute(): Asset
    {
        $this->authorize();
        $this->validate();
        $this->archive();
        $this->log();

        return $this->asset;
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
        if ($this->asset->isAssigned()) {
            throw new InvalidArgumentException('Somebody still has that piece of equipment');
        }
    }

    private function archive(): void
    {
        $this->asset->archived_at = now();
        $this->asset->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->asset->company,
            user: $this->author,
            action: UserActionEnum::AssetArchive,
            parameters: ['tag' => $this->asset->asset_tag],
        )->onQueue('low');
    }
}
