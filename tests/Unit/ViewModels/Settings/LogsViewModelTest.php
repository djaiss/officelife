<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings;

use App\Enums\UserActionEnum;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Log;
use App\Models\User;
use App\ViewModels\Settings\LogsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_logs_of_the_user_newest_first(): void
    {
        $user = User::factory()->create();

        Log::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::UserLogin->value,
            'created_at' => now()->subDays(3),
        ]);
        Log::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::UserPasswordUpdate->value,
            'created_at' => now()->subDay(),
        ]);

        $viewModel = new LogsViewModel(user: $user, employee: null);
        $logs = $viewModel->logs();

        $this->assertCount(2, $logs);
        $this->assertEquals(UserActionEnum::UserPasswordUpdate->value, $logs[0]->action);
        $this->assertEquals(UserActionEnum::UserLogin->value, $logs[1]->action);
    }

    #[Test]
    public function it_leaves_out_the_logs_of_somebody_else(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $colleague = User::factory()->create(['company_id' => $company->id]);

        Log::factory()->create([
            'company_id' => $company->id,
            'user_id' => $colleague->id,
        ]);

        $viewModel = new LogsViewModel(user: $user, employee: null);

        $this->assertCount(0, $viewModel->logs());
    }

    #[Test]
    public function it_gives_ten_logs_a_page(): void
    {
        $user = User::factory()->create();

        Log::factory()->count(12)->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);

        $viewModel = new LogsViewModel(user: $user, employee: null);

        $this->assertCount(10, $viewModel->logs());
        $this->assertTrue($viewModel->logs()->hasMorePages());
    }

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

        $viewModel = new LogsViewModel(user: $user, employee: $employee);

        $this->assertEquals('Big Tuna', $viewModel->name());
        $this->assertEquals('Dunder Mifflin', $viewModel->companyName());
    }

    #[Test]
    public function it_falls_back_to_the_email_when_there_is_no_employee_record(): void
    {
        $user = User::factory()->create([
            'employee_id' => null,
            'email' => 'accountant@vancerefrigeration.com',
        ]);

        $viewModel = new LogsViewModel(user: $user, employee: null);

        $this->assertEquals('accountant@vancerefrigeration.com', $viewModel->name());
        $this->assertNull($viewModel->employee());
    }

    #[Test]
    public function it_gives_the_employee_record_the_avatar_draws_from(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $viewModel = new LogsViewModel(user: $user, employee: $employee);

        $this->assertTrue($employee->is($viewModel->employee()));
    }
}
