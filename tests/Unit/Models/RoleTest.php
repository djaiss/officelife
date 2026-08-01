<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $company = Company::factory()->create();
        $role = Role::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($role->company()->exists());
        $this->assertEquals($company->id, $role->company->id);
    }

    #[Test]
    public function it_has_many_permissions(): void
    {
        $role = Role::factory()->create();
        RolePermission::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($role->permissions()->exists());
    }

    #[Test]
    public function it_has_many_users(): void
    {
        $company = Company::factory()->create();
        $role = Role::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $user->roles()->attach($role->id);

        $this->assertTrue($role->users()->exists());
        $this->assertEquals($user->id, $role->users()->first()->id);
    }
}
