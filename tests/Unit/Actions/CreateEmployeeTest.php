<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateEmployee;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateEmployeeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_an_employee(): void
    {
        $company = Company::factory()->create();

        $employee = new CreateEmployee(
            company: $company,
            firstName: 'Michael',
            lastName: 'Scott',
        )->execute();

        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'company_id' => $company->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
    }
}
