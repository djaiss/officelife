<?php

declare(strict_types=1);

namespace Tests\Unit\Permissions;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Permissions\EmployeeScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeScopeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_everybody_in_the_company_at_company_scope(): void
    {
        $company = Company::factory()->create();
        Employee::factory()->count(3)->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::EmployeeView, ScopeEnum::Company);

        $this->assertEquals(3, EmployeeScope::for($user, PermissionEnum::EmployeeView)->count());
    }

    #[Test]
    public function it_returns_only_their_own_record_at_self_scope(): void
    {
        $company = Company::factory()->create();
        $jim = Employee::factory()->create(['company_id' => $company->id]);
        Employee::factory()->count(3)->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::EmployeeUpdate, ScopeEnum::Self, $jim);

        $employees = EmployeeScope::for($user, PermissionEnum::EmployeeUpdate)->get();

        $this->assertCount(1, $employees);
        $this->assertEquals($jim->id, $employees->first()->id);
    }

    #[Test]
    public function it_returns_nobody_when_the_user_has_no_employee_at_self_scope(): void
    {
        $company = Company::factory()->create();
        Employee::factory()->count(3)->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::EmployeeUpdate, ScopeEnum::Self);

        $this->assertEquals(0, EmployeeScope::for($user, PermissionEnum::EmployeeUpdate)->count());
    }

    #[Test]
    public function it_returns_nobody_when_no_role_grants_the_permission(): void
    {
        $company = Company::factory()->create();
        Employee::factory()->count(3)->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::EmployeeView, ScopeEnum::Company);

        $this->assertEquals(0, EmployeeScope::for($user, PermissionEnum::EmployeeUpdate)->count());
    }

    #[Test]
    public function it_never_reaches_into_another_company(): void
    {
        $dunderMifflin = Company::factory()->create();
        Employee::factory()->count(2)->create(['company_id' => $dunderMifflin->id]);

        $michaelScottPaperCompany = Company::factory()->create();
        Employee::factory()->count(4)->create(['company_id' => $michaelScottPaperCompany->id]);

        $user = $this->userWith($dunderMifflin, PermissionEnum::EmployeeView, ScopeEnum::Company);

        $employees = EmployeeScope::for($user, PermissionEnum::EmployeeView)->get();

        $this->assertCount(2, $employees);
        $this->assertEquals([$dunderMifflin->id], $employees->pluck('company_id')->unique()->all());
    }

    #[Test]
    public function it_returns_everybody_in_the_company_for_the_owner(): void
    {
        $company = Company::factory()->create();
        Employee::factory()->count(3)->create(['company_id' => $company->id]);
        $owner = User::factory()->create(['company_id' => $company->id]);
        $company->owner_user_id = $owner->id;
        $company->save();

        $this->assertEquals(3, EmployeeScope::for($owner, PermissionEnum::EmployeeView)->count());
    }

    #[Test]
    public function it_refuses_a_permission_that_covers_the_whole_company(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $user = $this->userWith($company, PermissionEnum::RoleManage, ScopeEnum::Company);

        EmployeeScope::for($user, PermissionEnum::RoleManage);
    }

    /**
     * The list and the check have to say the same thing, or an employee that
     * cannot be opened still shows up on a screen. This walks every employee of
     * two companies and holds one answer against the other.
     */
    #[Test]
    public function it_agrees_with_the_check_made_one_employee_at_a_time(): void
    {
        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();

        $pam = Employee::factory()->create(['company_id' => $dunderMifflin->id]);
        Employee::factory()->count(3)->create(['company_id' => $dunderMifflin->id]);
        Employee::factory()->count(3)->create(['company_id' => $michaelScottPaperCompany->id]);

        $everybody = Employee::query()->get();

        foreach ([ScopeEnum::Self, ScopeEnum::Company] as $scope) {
            $user = $this->userWith($dunderMifflin, PermissionEnum::EmployeeUpdate, $scope, $pam);
            $allowed = EmployeeScope::for($user, PermissionEnum::EmployeeUpdate)->pluck('id')->all();

            foreach ($everybody as $employee) {
                $this->assertEquals(
                    $user->permission(PermissionEnum::EmployeeUpdate)->forEmployee($employee)->allowed(),
                    in_array($employee->id, $allowed, true),
                    'The list and the check disagree about employee '.$employee->id.' at '.$scope->value.' scope',
                );
            }
        }
    }

    private function userWith(Company $company, PermissionEnum $permission, ScopeEnum $scope, ?Employee $employee = null): User
    {
        $role = Role::factory()->create(['company_id' => $company->id]);

        RolePermission::factory()->create([
            'role_id' => $role->id,
            'permission' => $permission,
            'scope' => $scope,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee?->id,
        ]);

        $user->roles()->attach($role->id);

        return $user;
    }
}
