<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AssetCategoryTypeEnum;
use App\Models\AssetCategory;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetCategory>
 */
class AssetCategoryFactory extends Factory
{
    protected $model = AssetCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->word().' equipment',
            'type' => AssetCategoryTypeEnum::Asset,
            'requires_acceptance' => false,
            'eula_text' => null,
            'send_checkout_email' => false,
        ];
    }

    /**
     * A category whose equipment nobody may hold without accepting the terms.
     */
    public function requiringAcceptance(): static
    {
        return $this->state(fn (array $attributes): array => [
            'requires_acceptance' => true,
            'eula_text' => 'Look after it, and give it back when you leave.',
        ]);
    }
}
