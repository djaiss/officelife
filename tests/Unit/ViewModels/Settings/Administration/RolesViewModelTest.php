<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Administration;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\ViewModels\Settings\Administration\RolesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RolesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_roles_of_the_company_with_what_each_one_holds(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Regional manager']);
        RolePermission::factory()->create(['role_id' => $role->id, 'permission' => PermissionEnum::EmployeeView]);
        $user->roles()->attach($role->id);

        Role::factory()->create(['company_id' => $company->id, 'name' => 'Temp']);
        Role::factory()->create(['company_id' => Company::factory()->create()->id, 'name' => 'Elsewhere']);

        $viewModel = new RolesViewModel(user: $user, role: $role);

        $list = $viewModel->list();

        $this->assertCount(2, $list);
        $this->assertEquals('Regional manager', $list[0]['name']);
        $this->assertEquals('1 permission · 1 person', $list[0]['summary']);
        $this->assertTrue($list[0]['selected']);
        $this->assertEquals('0 permissions · nobody', $list[1]['summary']);
        $this->assertFalse($list[1]['selected']);
        $this->assertEquals('Roles · 2', $viewModel->rolesHeader());
    }

    #[Test]
    public function it_marks_the_permissions_the_role_grants_and_the_scope_of_each(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::factory()->create(['company_id' => $company->id]);
        RolePermission::factory()->create([
            'role_id' => $role->id,
            'permission' => PermissionEnum::EmployeeUpdate,
            'scope' => ScopeEnum::Self,
        ]);

        $groups = new RolesViewModel(user: $user, role: $role)->groups();

        $permissions = collect($groups)->pluck('permissions')->flatten(1)->keyBy('value');

        $this->assertTrue($permissions[PermissionEnum::EmployeeUpdate->value]['granted']);
        $this->assertEquals(ScopeEnum::Self->value, $permissions[PermissionEnum::EmployeeUpdate->value]['scope']);

        $this->assertFalse($permissions[PermissionEnum::EmployeeView->value]['granted']);
        $this->assertEquals(ScopeEnum::Company->value, $permissions[PermissionEnum::EmployeeView->value]['scope']);
    }

    #[Test]
    public function it_offers_no_scope_for_a_permission_that_covers_the_whole_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $role = Role::factory()->create(['company_id' => $company->id]);

        $groups = new RolesViewModel(user: $user, role: $role)->groups();

        $permissions = collect($groups)->pluck('permissions')->flatten(1)->keyBy('value');

        $this->assertEquals([], $permissions[PermissionEnum::CompanyManage->value]['scopes']);
        $this->assertFalse($permissions[PermissionEnum::CompanyManage->value]['targetsEmployee']);
        $this->assertCount(2, $permissions[PermissionEnum::EmployeeView->value]['scopes']);
    }

    #[Test]
    public function it_counts_what_the_role_grants_out_of_everything_on_offer(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::factory()->create(['company_id' => $company->id]);
        RolePermission::factory()->create(['role_id' => $role->id, 'permission' => PermissionEnum::EmployeeView]);

        $viewModel = new RolesViewModel(user: $user, role: $role);

        $this->assertEquals('1 of '.count(PermissionEnum::cases()).' granted', $viewModel->grantCountLabel());
    }

    #[Test]
    public function it_warns_about_a_role_that_administers_the_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $quiet = Role::factory()->create(['company_id' => $company->id]);
        $this->assertFalse(new RolesViewModel(user: $user, role: $quiet)->warnsAboutAdministration());

        $loud = Role::factory()->create(['company_id' => $company->id]);
        RolePermission::factory()->create(['role_id' => $loud->id, 'permission' => PermissionEnum::RoleManage]);

        $this->assertTrue(new RolesViewModel(user: $user, role: $loud)->warnsAboutAdministration());
    }

    #[Test]
    public function it_splits_the_company_into_who_holds_the_role_and_who_does_not(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'email' => 'michael@dundermifflin.com']);
        $colleague = User::factory()->create(['company_id' => $company->id, 'email' => 'jim@dundermifflin.com']);
        User::factory()->create(['company_id' => Company::factory()->create()->id, 'email' => 'stranger@example.com']);

        $role = Role::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($role->id);

        $viewModel = new RolesViewModel(user: $user, role: $role);

        $this->assertCount(1, $viewModel->people());
        $this->assertEquals('michael@dundermifflin.com', $viewModel->people()[0]['email']);

        $this->assertCount(1, $viewModel->assignable());
        $this->assertEquals('jim@dundermifflin.com', $viewModel->assignable()[0]['email']);
    }

    #[Test]
    public function it_says_why_a_role_cannot_be_deleted(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $free = Role::factory()->create(['company_id' => $company->id]);
        $viewModel = new RolesViewModel(user: $user, role: $free);
        $this->assertTrue($viewModel->canBeDeleted());
        $this->assertNull($viewModel->deleteHint());

        $held = Role::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($held->id);
        $viewModel = new RolesViewModel(user: $user, role: $held);
        $this->assertFalse($viewModel->canBeDeleted());
        $this->assertEquals('held by 1', $viewModel->deleteHint());

        $locked = Role::factory()->locked()->create(['company_id' => $company->id]);
        $viewModel = new RolesViewModel(user: $user, role: $locked);
        $this->assertFalse($viewModel->canBeDeleted());
        $this->assertEquals('not editable', $viewModel->deleteHint());
    }

    #[Test]
    public function it_offers_every_role_as_a_starting_point_for_a_new_one(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Role::factory()->create(['company_id' => $company->id, 'name' => 'Regional manager']);

        $templates = new RolesViewModel(user: $user, role: null)->templates();

        $this->assertCount(2, $templates);
        $this->assertEquals(['id' => '', 'name' => 'Nothing'], $templates[0]);
        $this->assertEquals('Regional manager', $templates[1]['name']);
    }

    #[Test]
    public function it_holds_nothing_when_the_company_has_no_roles_left(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $viewModel = new RolesViewModel(user: $user, role: null);

        $this->assertNull($viewModel->role());
        $this->assertEquals([], $viewModel->list());
        $this->assertEquals([], $viewModel->people());
        $this->assertEquals([], $viewModel->assignable());
        $this->assertFalse($viewModel->canBeDeleted());
        $this->assertEquals('Roles · 0', $viewModel->rolesHeader());
    }

    #[Test]
    public function it_writes_out_what_each_scope_button_means(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $legend = new RolesViewModel(user: $user, role: null)->scopeLegend();

        $this->assertCount(count(ScopeEnum::cases()), $legend);
        $this->assertEquals(['short' => 'Self', 'label' => 'Themselves only'], $legend[0]);
    }
}
