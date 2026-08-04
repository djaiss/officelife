<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OccurrenceActorEnum;
use App\Enums\OccurrenceTypeEnum;
use App\Models\Company;
use App\Models\Occurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Occurrence>
 */
class OccurrenceFactory extends Factory
{
    protected $model = Occurrence::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => OccurrenceTypeEnum::EmployeeCreated,
            'source' => Occurrence::SOURCE_INTERNAL,
            'subject_type' => null,
            'subject_id' => null,
            'actor_type' => OccurrenceActorEnum::System,
            'actor_id' => null,
            'payload' => null,
            'occurred_at' => now(),
        ];
    }

    /**
     * An event reported by something outside the application.
     */
    public function fromIntegration(string $name): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => 'integration:'.$name,
            'actor_type' => OccurrenceActorEnum::Integration,
        ]);
    }
}
