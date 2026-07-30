<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $employee = Employee::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($employee->company()->exists());
        $this->assertEquals('Dunder Mifflin', $employee->company->name);
    }

    #[Test]
    public function it_has_one_user(): void
    {
        $employee = Employee::factory()->create();
        User::factory()->create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $this->assertTrue($employee->user()->exists());
        $this->assertEquals('michael.scott@dundermifflin.com', $employee->user->email);
    }

    #[Test]
    public function it_has_no_user_when_the_employee_has_no_account(): void
    {
        $employee = Employee::factory()->create();

        $this->assertNull($employee->user);
    }

    #[Test]
    public function it_returns_the_full_name(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        $this->assertEquals('Michael Scott', $employee->name);
    }
}
