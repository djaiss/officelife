<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Asset;
use App\Models\User;

/**
 * Bring a piece of equipment back into the fleet. It returns in whatever state
 * it was archived in, which is left for somebody to correct rather than guessed
 * at here.
 */
class RestoreAsset
{
    public function __construct(
        private readonly User $author,
        private readonly Asset $asset,
    ) {}

    public function execute(): Asset
    {
        $this->authorize();
        $this->restore();
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

    private function restore(): void
    {
        $this->asset->archived_at = null;
        $this->asset->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->asset->company,
            user: $this->author,
            action: UserActionEnum::AssetRestoration,
            parameters: ['tag' => $this->asset->asset_tag],
        )->onQueue('low');
    }
}
