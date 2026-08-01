<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->city().' office',
            'country' => fake()->countryCode(),
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
            'timezone' => 'UTC',
        ];
    }
}
