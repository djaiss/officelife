<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Manufacturer>
 */
class ManufacturerFactory extends Factory
{
    protected $model = Manufacturer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->company(),
            'website_url' => fake()->url(),
            'support_url' => fake()->url(),
            'support_email' => fake()->safeEmail(),
            'support_phone' => fake()->phoneNumber(),
            'notes' => null,
        ];
    }
}
