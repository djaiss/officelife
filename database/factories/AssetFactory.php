<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::factory();

        return [
            'company_id' => $company,
            'asset_model_id' => AssetModel::factory()->for($company),
            'status_id' => fn (): int => AssetStatus::query()
                ->where('key', AssetStatus::READY_TO_DEPLOY)
                ->value('id'),
            'asset_tag' => fake()->unique()->bothify('OL-####-??'),
            'serial_number' => fake()->unique()->bothify('SN#########'),
            'name' => null,
            'purchase_date' => now()->subYear(),
            'purchase_cost' => 249900,
            'is_byod' => false,
            'is_requestable' => false,
        ];
    }

    /**
     * A piece of equipment in a state it cannot be handed out in.
     */
    public function undeployable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_id' => AssetStatus::query()
                ->where('key', AssetStatus::AWAITING_REPAIR)
                ->value('id'),
        ]);
    }

    /**
     * A piece of equipment that has left the fleet.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'archived_at' => now(),
        ]);
    }
}
