<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AssetAssigneeTypeEnum;
use App\Enums\AssetConditionEnum;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<AssetAssignment>
 */
class AssetAssignmentFactory extends Factory
{
    protected $model = AssetAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'assignee_type' => AssetAssigneeTypeEnum::Employee,
            'assignee_id' => Employee::factory(),
            'assigned_by_user_id' => null,
            'assigned_at' => now(),
            'expected_return_at' => null,
            'returned_at' => null,
            'condition_at_checkout' => AssetConditionEnum::Good,
        ];
    }

    /**
     * An assignment somebody has already closed.
     */
    public function returned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'returned_at' => now(),
            'condition_at_checkin' => AssetConditionEnum::Good,
        ]);
    }

    /**
     * An assignment whose equipment was due back before now.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expected_return_at' => now()->subWeek(),
            'returned_at' => null,
        ]);
    }

    /**
     * An assignment to something other than a colleague.
     */
    public function to(Model $assignee): static
    {
        return $this->state(fn (array $attributes): array => [
            'assignee_type' => AssetAssigneeTypeEnum::forModel($assignee),
            'assignee_id' => $assignee->getKey(),
        ]);
    }
}
