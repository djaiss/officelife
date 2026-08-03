<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\DomainEventActorEnum;
use App\Enums\DomainEventTypeEnum;
use App\Events\DomainEventOccurred;
use App\Models\Company;
use App\Models\DomainEvent;
use App\Models\Employee;
use App\Models\User;
use App\Services\DomainEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DomainEventsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_event_down(): void
    {
        Event::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $actor = User::factory()->create(['company_id' => $company->id]);

        $event = DomainEvents::publish(
            type: DomainEventTypeEnum::EmployeeCreated,
            company: $company,
            subject: $employee,
            actor: $actor,
            payload: ['name' => 'Dwight Schrute'],
        );

        $this->assertInstanceOf(DomainEvent::class, $event);
        $this->assertDatabaseHas('domain_events', [
            'id' => $event->id,
            'company_id' => $company->id,
            'type' => 'employee.created',
            'source' => 'internal',
            'subject_type' => Employee::class,
            'subject_id' => $employee->id,
            'actor_type' => 'user',
            'actor_id' => $actor->id,
        ]);
        $this->assertEquals(['name' => 'Dwight Schrute'], $event->payload);
    }

    #[Test]
    public function it_dispatches_exactly_one_internal_event_whatever_the_type(): void
    {
        Event::fake();

        $company = Company::factory()->create();

        DomainEvents::publish(type: DomainEventTypeEnum::CompanyCreated, company: $company);
        DomainEvents::publish(type: DomainEventTypeEnum::CompanyUpdated, company: $company);

        Event::assertDispatchedTimes(DomainEventOccurred::class, 2);
    }

    #[Test]
    public function it_writes_the_event_before_anything_reacts_to_it(): void
    {
        $company = Company::factory()->create();
        $seen = null;

        Event::listen(DomainEventOccurred::class, function (DomainEventOccurred $event) use (&$seen): void {
            $seen = DomainEvent::query()->find($event->domainEvent->id);
        });

        DomainEvents::publish(type: DomainEventTypeEnum::CompanyCreated, company: $company);

        $this->assertNotNull($seen);
    }

    #[Test]
    public function it_records_the_system_as_the_actor_when_nobody_caused_it(): void
    {
        Event::fake();

        $event = DomainEvents::publish(
            type: DomainEventTypeEnum::AssetReturnOverdue,
            company: Company::factory()->create(),
        );

        $this->assertEquals(DomainEventActorEnum::System, $event->actor_type);
        $this->assertNull($event->actor_id);
    }

    #[Test]
    public function it_allows_an_event_that_belongs_to_no_company(): void
    {
        Event::fake();

        $event = DomainEvents::publish(type: DomainEventTypeEnum::CompanyCreated);

        $this->assertNull($event->company_id);
        $this->assertDatabaseHas('domain_events', ['id' => $event->id, 'company_id' => null]);
    }

    #[Test]
    public function it_keeps_when_it_happened_apart_from_when_it_was_written(): void
    {
        Event::fake();

        $occurred = now()->subMinutes(4);

        $event = DomainEvents::publish(
            type: DomainEventTypeEnum::EmployeeCreated,
            company: Company::factory()->create(),
            occurredAt: $occurred,
            source: 'integration:github',
        );

        $this->assertEqualsWithDelta($occurred->timestamp, $event->occurred_at->timestamp, 1);
        $this->assertEqualsWithDelta(now()->timestamp, $event->created_at->timestamp, 2);
        $this->assertEquals('integration:github', $event->source);
    }
}
