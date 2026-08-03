<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Company;
use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetModel>
 */
class AssetModelFactory extends Factory
{
    protected $model = AssetModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::factory();

        return [
            'company_id' => $company,
            'manufacturer_id' => Manufacturer::factory()->for($company),
            'asset_category_id' => AssetCategory::factory()->for($company),
            'name' => fake()->unique()->words(3, true),
            'model_number' => fake()->bothify('??-####'),
            'image_path' => null,
            'useful_life_months' => 36,
            'is_requestable' => false,
            'notes' => null,
        ];
    }
}
