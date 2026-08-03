<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\AssetModel;
use App\Models\User;
use InvalidArgumentException;

/**
 * Remove a model from the catalogue. Refused while the company still owns
 * equipment of that model, archived equipment included: what an asset is has to
 * stay readable for as long as the asset does.
 */
class DestroyAssetModel
{
    public function __construct(
        private readonly User $author,
        private readonly AssetModel $assetModel,
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
            ->forCompany($this->assetModel->company)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->assetModel->assets()->exists()) {
            throw new InvalidArgumentException('The company still owns equipment of that model');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->assetModel->company,
            user: $this->author,
            action: UserActionEnum::AssetModelDeletion,
            parameters: ['name' => $this->assetModel->name],
        )->onQueue('low');
    }

    private function destroy(): void
    {
        $this->assetModel->delete();
    }
}
