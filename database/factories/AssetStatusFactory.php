<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AssetStatusTypeEnum;
use App\Models\AssetStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetStatus>
 */
class AssetStatusFactory extends Factory
{
    protected $model = AssetStatus::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'key' => null,
            'name' => fake()->unique()->words(2, true),
            'type' => AssetStatusTypeEnum::Deployable,
            'color' => '#16a34a',
            'is_system' => false,
        ];
    }

    /**
     * A status equipment cannot be handed out in.
     */
    public function undeployable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => AssetStatusTypeEnum::Undeployable,
        ]);
    }
}
