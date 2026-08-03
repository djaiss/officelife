<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Company;
use App\Models\Manufacturer;
use App\Models\User;
use InvalidArgumentException;

/**
 * Add a model to the catalogue of a company. Every piece of equipment belongs to
 * one, which is what stops the manufacturer of forty identical laptops being
 * typed forty times.
 */
class CreateAssetModel
{
    private AssetModel $assetModel;

    public function __construct(
        private readonly User $author,
        private readonly Company $company,
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
        $this->create();
        $this->log();

        return $this->assetModel;
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
        $this->modelNumber = TextSanitizer::nullablePlainText($this->modelNumber);
        $this->notes = TextSanitizer::nullablePlainText($this->notes);
    }

    /**
     * The manufacturer and the category have to belong to the same company as
     * the model, or the catalogue of one company starts referring to another.
     */
    private function validate(): void
    {
        if ($this->name === '') {
            throw new InvalidArgumentException('A model needs a name');
        }

        if ($this->manufacturer->company_id !== $this->company->id) {
            throw new InvalidArgumentException('The manufacturer belongs to another company');
        }

        if ($this->category->company_id !== $this->company->id) {
            throw new InvalidArgumentException('The category belongs to another company');
        }

        if (! $this->category->type->isAvailable()) {
            throw new InvalidArgumentException('Nothing can be recorded against a '.$this->category->type->value.' category yet');
        }

        $taken = AssetModel::query()
            ->where('company_id', $this->company->id)
            ->where('name', $this->name)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already has a model called '.$this->name);
        }
    }

    private function create(): void
    {
        $this->assetModel = AssetModel::query()->create([
            'company_id' => $this->company->id,
            'manufacturer_id' => $this->manufacturer->id,
            'asset_category_id' => $this->category->id,
            'name' => $this->name,
            'model_number' => $this->modelNumber,
            'useful_life_months' => $this->usefulLifeMonths,
            'is_requestable' => $this->isRequestable,
            'notes' => $this->notes,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::AssetModelCreation,
            parameters: ['name' => $this->assetModel->name],
        )->onQueue('low');
    }
}
