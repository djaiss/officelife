<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $held = UserRole::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($held->user()->exists());
        $this->assertEquals($user->id, $held->user->id);
    }

    #[Test]
    public function it_belongs_to_a_role(): void
    {
        $role = Role::factory()->create();
        $held = UserRole::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($held->role()->exists());
        $this->assertEquals($role->id, $held->role->id);
    }

    /**
     * The screen that says how long somebody has held a role reads this, so it
     * has to come back as a date rather than as whatever the database wrote.
     */
    #[Test]
    public function it_says_when_the_role_was_handed_out(): void
    {
        $held = UserRole::factory()->create();

        $this->assertNotNull($held->created_at);
        $this->assertEqualsWithDelta(now()->timestamp, $held->created_at->timestamp, 2);
    }
}
