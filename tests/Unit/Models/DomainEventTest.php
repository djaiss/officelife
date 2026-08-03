<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\DomainEventActorEnum;
use App\Enums\DomainEventTypeEnum;
use App\Models\Company;
use App\Models\DomainEvent;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DomainEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $event = DomainEvent::factory()->create();

        $this->assertTrue($event->company()->exists());
        $this->assertInstanceOf(Company::class, $event->company);
    }

    #[Test]
    public function it_can_be_about_any_model_at_all(): void
    {
        $employee = Employee::factory()->create();
        $event = DomainEvent::factory()->create([
            'company_id' => $employee->company_id,
            'subject_type' => Employee::class,
            'subject_id' => $employee->id,
        ]);

        $this->assertInstanceOf(Employee::class, $event->subject);
        $this->assertEquals($employee->id, $event->subject->id);
    }

    #[Test]
    public function it_records_the_user_who_caused_it(): void
    {
        $user = User::factory()->create();
        $event = DomainEvent::factory()->create([
            'company_id' => $user->company_id,
            'actor_type' => DomainEventActorEnum::User,
            'actor_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $event->actor);
        $this->assertEquals($user->id, $event->actor->id);
    }

    #[Test]
    public function it_is_written_once_and_never_stamped_with_an_update(): void
    {
        $event = DomainEvent::factory()->create();

        $this->assertNotNull($event->created_at);
        $this->assertNull(DomainEvent::UPDATED_AT);
    }

    #[Test]
    public function it_reads_its_type_back_as_a_case_of_the_catalogue(): void
    {
        $event = DomainEvent::factory()->create(['type' => DomainEventTypeEnum::AssetCheckedOut]);

        $this->assertEquals(DomainEventTypeEnum::AssetCheckedOut, $event->fresh()->type);
    }
}
