<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AssetCategoryTypeEnum;
use App\Models\AssetCategory;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Give a company a catalogue to start from when it turns the assets module on.
 * Nobody asks for this: without it, recording the first laptop means inventing
 * a category, then a manufacturer, then a model, which is three levels of
 * setup before anything is worth looking at.
 */
class CreateDefaultAssetCategories
{
    /** @var Collection<int, AssetCategory> */
    private Collection $categories;

    public function __construct(
        private readonly Company $company,
    ) {}

    /** @return Collection<int, AssetCategory> */
    public function execute(): Collection
    {
        $this->categories = new Collection;

        if ($this->company->assetCategories()->exists()) {
            return $this->categories;
        }

        DB::transaction(function (): void {
            foreach ($this->names() as $name) {
                $this->createCategory($name);
            }
        });

        return $this->categories;
    }

    /** @return list<string> */
    private function names(): array
    {
        return [
            'Laptops',
            'Desktops',
            'Monitors',
            'Phones',
            'Tablets',
            'Docking stations',
            'Security badges',
        ];
    }

    private function createCategory(string $name): void
    {
        $this->categories->push(AssetCategory::query()->create([
            'company_id' => $this->company->id,
            'name' => null,
            'name_translation_key' => $name,
            'type' => AssetCategoryTypeEnum::Asset,
            'requires_acceptance' => false,
            'eula_text' => null,
            'send_checkout_email' => false,
        ]));
    }
}
