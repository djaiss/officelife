<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PermissionEnumTest extends TestCase
{
    #[Test]
    public function it_knows_which_permissions_are_about_one_employee(): void
    {
        $this->assertTrue(PermissionEnum::EmployeeView->targetsEmployee());
        $this->assertTrue(PermissionEnum::EmployeeUpdate->targetsEmployee());
        $this->assertTrue(PermissionEnum::EmployeeViewPrivate->targetsEmployee());
        $this->assertTrue(PermissionEnum::EmployeeUpdatePrivate->targetsEmployee());

        $this->assertFalse(PermissionEnum::EmployeeCreate->targetsEmployee());
        $this->assertFalse(PermissionEnum::RoleManage->targetsEmployee());
        $this->assertFalse(PermissionEnum::CompanyManage->targetsEmployee());
    }

    #[Test]
    public function it_offers_no_choice_of_scope_for_a_company_wide_permission(): void
    {
        $this->assertEquals([ScopeEnum::Company], PermissionEnum::EmployeeCreate->scopes());
        $this->assertEquals([ScopeEnum::Company], PermissionEnum::RoleManage->scopes());
        $this->assertEquals([ScopeEnum::Company], PermissionEnum::CompanyManage->scopes());
    }

    #[Test]
    public function it_offers_both_scopes_for_a_permission_about_one_employee(): void
    {
        $this->assertEquals(
            [ScopeEnum::Self, ScopeEnum::Company],
            PermissionEnum::EmployeeUpdate->scopes(),
        );
    }

    #[Test]
    public function it_names_every_permission(): void
    {
        foreach (PermissionEnum::cases() as $permission) {
            $this->assertNotEmpty($permission->label());
        }
    }
}
