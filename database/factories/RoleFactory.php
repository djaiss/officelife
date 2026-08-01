<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'company_id' => Company::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'is_default' => false,
            'is_editable' => true,
        ];
    }

    /**
     * Indicate that the role is one of the roles every company starts with.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the role is locked, so nobody may rename, regrant or delete
     * it.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_editable' => false,
        ]);
    }
}
