<?php

declare(strict_types=1);

namespace Tests\Unit\Permissions;

use App\Enums\ModuleEnum;
use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModulePermissionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_denies_a_permission_of_a_module_the_company_has_not_turned_on(): void
    {
        $company = Company::factory()->create();
        $user = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::AssetManage);

        $this->assertFalse($user->permission(PermissionEnum::AssetManage)->forCompany($company)->allowed());
    }

    #[Test]
    public function it_allows_the_same_permission_once_the_module_is_on(): void
    {
        $company = Company::factory()->withModule(ModuleEnum::Assets)->create();
        $user = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::AssetManage);

        $this->assertTrue($user->permission(PermissionEnum::AssetManage)->forCompany($company)->allowed());
    }

    #[Test]
    public function it_denies_the_owner_of_the_company_while_the_module_is_off(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $company->owner_user_id = $owner->id;
        $company->save();

        $this->assertTrue($owner->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed());
        $this->assertFalse($owner->permission(PermissionEnum::AssetManage)->forCompany($company)->allowed());
    }

    #[Test]
    public function it_holds_back_no_core_permission_whatever_the_modules(): void
    {
        $company = Company::factory()->create();
        $user = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);

        $this->assertTrue($user->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed());
    }

    #[Test]
    public function it_keeps_the_grant_so_turning_the_module_back_on_needs_no_reconfiguration(): void
    {
        $company = Company::factory()->withModule(ModuleEnum::Assets)->create();
        $user = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::AssetCheckout);

        $company->settings = ['modules' => []];
        $company->save();
        $user->forgetGrants();

        $this->assertFalse($user->permission(PermissionEnum::AssetCheckout)->forCompany($company->fresh())->allowed());

        $company->settings = ['modules' => ['assets']];
        $company->save();

        $this->assertTrue($user->permission(PermissionEnum::AssetCheckout)->forCompany($company->fresh())->allowed());
    }

    #[Test]
    public function it_knows_which_permissions_belong_to_a_module(): void
    {
        $this->assertEquals(ModuleEnum::Assets, PermissionEnum::AssetView->module());
        $this->assertEquals(ModuleEnum::Assets, PermissionEnum::AssetManage->module());
        $this->assertEquals(ModuleEnum::Assets, PermissionEnum::AssetCheckout->module());

        $this->assertNull(PermissionEnum::CompanyManage->module());
        $this->assertNull(PermissionEnum::EmployeeView->module());
    }
}
