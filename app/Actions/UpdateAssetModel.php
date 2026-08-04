<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Manufacturer;
use App\Models\User;
use InvalidArgumentException;

/**
 * Change a model of the catalogue.
 */
class UpdateAssetModel
{
    public function __construct(
        private readonly User $author,
        private readonly AssetModel $assetModel,
        private readonly Manufacturer $manufacturer,
        private readonly AssetCategory $category,
        private string $name,
        private ?string $modelNumber = null,
        private readonly ?int $usefulLifeMonths = null,
        private readonly bool $isRequestable = false,
        private ?string $notes = null,
    ) {}

    public function execute(): AssetModel
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->assetModel;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::AssetManage)
            ->forCompany($this->assetModel->company)
            ->authorize();
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->modelNumber = TextSanitizer::nullablePlainText($this->modelNumber);
        $this->notes = TextSanitizer::nullablePlainText($this->notes);
    }

    private function validate(): void
    {
        if ($this->name === '') {
            throw new InvalidArgumentException('A model needs a name');
        }

        if ($this->manufacturer->company_id !== $this->assetModel->company_id) {
            throw new InvalidArgumentException('The manufacturer belongs to another company');
        }

        if ($this->category->company_id !== $this->assetModel->company_id) {
            throw new InvalidArgumentException('The category belongs to another company');
        }

        if (! $this->category->type->isAvailable()) {
            throw new InvalidArgumentException('Nothing can be recorded against a '.$this->category->type->value.' category yet');
        }

        $taken = AssetModel::query()
            ->where('company_id', $this->assetModel->company_id)
            ->where('name', $this->name)
            ->whereKeyNot($this->assetModel->id)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already has a model called '.$this->name);
        }
    }

    private function update(): void
    {
        $this->assetModel->manufacturer_id = $this->manufacturer->id;
        $this->assetModel->asset_category_id = $this->category->id;
        $this->assetModel->name = $this->name;
        $this->assetModel->model_number = $this->modelNumber;
        $this->assetModel->useful_life_months = $this->usefulLifeMonths;
        $this->assetModel->is_requestable = $this->isRequestable;
        $this->assetModel->notes = $this->notes;
        $this->assetModel->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->assetModel->company,
            user: $this->author,
            action: UserActionEnum::AssetModelUpdate,
            parameters: ['name' => $this->assetModel->name],
        )->onQueue('low');
    }
}
