<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\AssetCategory;
use App\Models\User;
use InvalidArgumentException;

/**
 * Remove a category from the catalogue. Refused while a model is still filed
 * under it.
 */
class DestroyAssetCategory
{
    public function __construct(
        private readonly User $author,
        private readonly AssetCategory $category,
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
            ->forCompany($this->category->company)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->category->assetModels()->exists()) {
            throw new InvalidArgumentException('The category still has equipment filed under it');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->category->company,
            user: $this->author,
            action: UserActionEnum::AssetCategoryDeletion,
            parameters: ['name' => $this->category->name],
        )->onQueue('low');
    }

    private function destroy(): void
    {
        $this->category->delete();
    }
}
