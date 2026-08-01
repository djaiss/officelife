<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Security;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\ViewModels\Settings\Account\Security\SecurityViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_name_and_the_company_of_the_signed_in_person(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
            'display_name' => 'Big Tuna',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $viewModel = new SecurityViewModel(user: $user, employee: $employee);

        $this->assertEquals('Big Tuna', $viewModel->name());
        $this->assertEquals('Dunder Mifflin', $viewModel->companyName());
        $this->assertTrue($viewModel->employee()->is($employee));
    }

    #[Test]
    public function it_falls_back_to_the_email_when_there_is_no_employee_record(): void
    {
        $user = User::factory()->create([
            'employee_id' => null,
            'email' => 'accountant@vancerefrigeration.com',
        ]);

        $viewModel = new SecurityViewModel(user: $user, employee: null);

        $this->assertEquals('accountant@vancerefrigeration.com', $viewModel->name());
        $this->assertNull($viewModel->employee());
    }

    #[Test]
    public function it_says_whether_the_account_signs_in_through_a_provider(): void
    {
        $user = User::factory()->create();
        $viewModel = new SecurityViewModel(user: $user, employee: null);

        $this->assertFalse($viewModel->usesSingleSignOn());

        $singleSignOnUser = User::factory()->singleSignOn()->create();
        $singleSignOnViewModel = new SecurityViewModel(user: $singleSignOnUser, employee: null);

        $this->assertTrue($singleSignOnViewModel->usesSingleSignOn());
    }
}
