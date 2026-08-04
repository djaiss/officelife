<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\PublishOccurrence;
use App\Enums\OccurrenceActorEnum;
use App\Enums\OccurrenceTypeEnum;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Occurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublishOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_down_what_happened(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $actor = User::factory()->create(['company_id' => $company->id]);

        $occurrence = new PublishOccurrence(
            type: OccurrenceTypeEnum::EmployeeCreated,
            company: $company,
            subject: $employee,
            actor: $actor,
            payload: ['name' => 'Dwight Schrute'],
        )->execute();

        $this->assertInstanceOf(Occurrence::class, $occurrence);
        $this->assertDatabaseHas('occurrences', [
            'id' => $occurrence->id,
            'company_id' => $company->id,
            'type' => 'employee.created',
            'source' => 'internal',
            'subject_type' => Employee::class,
            'subject_id' => $employee->id,
            'actor_type' => 'user',
            'actor_id' => $actor->id,
        ]);
        $this->assertEquals(['name' => 'Dwight Schrute'], $occurrence->payload);
    }

    #[Test]
    public function it_writes_one_row_for_each_thing_that_happened(): void
    {
        $company = Company::factory()->create();

        new PublishOccurrence(type: OccurrenceTypeEnum::CompanyCreated, company: $company)->execute();
        new PublishOccurrence(type: OccurrenceTypeEnum::CompanyUpdated, company: $company)->execute();

        $this->assertEquals(2, Occurrence::query()->count());
    }

    #[Test]
    public function it_can_be_read_back_long_afterwards(): void
    {
        $occurrence = new PublishOccurrence(
            type: OccurrenceTypeEnum::CompanyCreated,
            company: Company::factory()->create(),
        )->execute();

        $this->assertNotNull(Occurrence::query()->find($occurrence->id));
    }

    #[Test]
    public function it_records_the_application_itself_as_the_cause_when_nobody_did_it(): void
    {
        $occurrence = new PublishOccurrence(
            type: OccurrenceTypeEnum::AssetReturnOverdue,
            company: Company::factory()->create(),
        )->execute();

        $this->assertEquals(OccurrenceActorEnum::System, $occurrence->actor_type);
        $this->assertNull($occurrence->actor_id);
    }

    #[Test]
    public function it_allows_something_that_belongs_to_no_company(): void
    {
        $occurrence = new PublishOccurrence(type: OccurrenceTypeEnum::CompanyCreated)->execute();

        $this->assertNull($occurrence->company_id);
        $this->assertDatabaseHas('occurrences', ['id' => $occurrence->id, 'company_id' => null]);
    }

    #[Test]
    public function it_keeps_when_it_happened_apart_from_when_it_was_written(): void
    {
        $occurred = now()->subMinutes(4);

        $occurrence = new PublishOccurrence(
            type: OccurrenceTypeEnum::EmployeeCreated,
            company: Company::factory()->create(),
            occurredAt: $occurred,
            source: 'integration:github',
        )->execute();

        $this->assertEqualsWithDelta($occurred->timestamp, $occurrence->occurred_at->timestamp, 1);
        $this->assertEqualsWithDelta(now()->timestamp, $occurrence->created_at->timestamp, 2);
        $this->assertEquals('integration:github', $occurrence->source);
    }
}
