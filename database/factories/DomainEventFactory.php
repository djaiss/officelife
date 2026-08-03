<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DomainEventActorEnum;
use App\Enums\DomainEventTypeEnum;
use App\Models\Company;
use App\Models\DomainEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DomainEvent>
 */
class DomainEventFactory extends Factory
{
    protected $model = DomainEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => DomainEventTypeEnum::EmployeeCreated,
            'source' => DomainEvent::SOURCE_INTERNAL,
            'subject_type' => null,
            'subject_id' => null,
            'actor_type' => DomainEventActorEnum::System,
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
            'actor_type' => DomainEventActorEnum::Integration,
        ]);
    }
}
