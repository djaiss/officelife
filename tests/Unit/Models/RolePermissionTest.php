<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_role(): void
    {
        $role = Role::factory()->create();
        $permission = RolePermission::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($permission->role()->exists());
        $this->assertEquals($role->id, $permission->role->id);
    }
}
