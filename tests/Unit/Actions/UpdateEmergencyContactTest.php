<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateEmergencyContact;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateEmergencyContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_emergency_contact_of_the_employee_signed_in(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->grant($user, PermissionEnum::EmployeeUpdatePrivate, ScopeEnum::Self);

        $result = new UpdateEmergencyContact(
            author: $user,
            employee: $employee,
            name: 'Mose Schrute',
            phone: '+1 570 555 0182',
            relationship: 'Cousin',
        )->execute();

        $this->assertInstanceOf(Employee::class, $result);

        $employee->refresh();

        $this->assertEquals('Mose Schrute', $employee->emergency_contact_name);
        $this->assertEquals('+1 570 555 0182', $employee->emergency_contact_phone);
        $this->assertEquals('Cousin', $employee->emergency_contact_relationship);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EmergencyContactUpdate
                && $job->company->id === $company->id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_stamps_when_the_record_was_last_saved(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'last_saved_at' => null,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->grant($user, PermissionEnum::EmployeeUpdatePrivate, ScopeEnum::Self);

        new UpdateEmergencyContact(
            author: $user,
            employee: $employee,
            name: 'Bob Vance',
        )->execute();

        $this->assertEqualsWithDelta(
            now()->timestamp,
            $employee->refresh()->last_saved_at?->timestamp,
            2,
        );
    }

    #[Test]
    public function it_clears_the_contact_when_nothing_is_given(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->withPrivateInformation()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->grant($user, PermissionEnum::EmployeeUpdatePrivate, ScopeEnum::Self);

        new UpdateEmergencyContact(author: $user, employee: $employee)->execute();

        $employee->refresh();

        $this->assertNull($employee->emergency_contact_name);
        $this->assertNull($employee->emergency_contact_phone);
        $this->assertNull($employee->emergency_contact_relationship);
    }

    #[Test]
    public function it_throws_when_the_author_may_only_edit_the_public_details(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $author = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->grant($author, PermissionEnum::EmployeeUpdate, ScopeEnum::Company);

        new UpdateEmergencyContact(
            author: $author,
            employee: $employee,
            name: 'Toby Flenderson',
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_only_edit_themselves(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $angela = Employee::factory()->create(['company_id' => $company->id]);
        $oscar = Employee::factory()->create(['company_id' => $company->id]);
        $author = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $angela->id,
        ]);
        $this->grant($author, PermissionEnum::EmployeeUpdatePrivate, ScopeEnum::Self);

        new UpdateEmergencyContact(
            author: $author,
            employee: $oscar,
            name: 'Toby Flenderson',
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_employee_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();
        $stranger = Employee::factory()->create(['company_id' => $michaelScottPaperCompany->id]);
        $author = User::factory()->create(['company_id' => $dunderMifflin->id]);
        $this->grant($author, PermissionEnum::EmployeeUpdatePrivate, ScopeEnum::Company);

        new UpdateEmergencyContact(
            author: $author,
            employee: $stranger,
            name: 'Toby Flenderson',
        )->execute();
    }
}
