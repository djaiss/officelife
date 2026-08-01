<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyRole;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyRoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_destroys_a_role_nobody_holds(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Salesman']);
        RolePermission::factory()->create(['role_id' => $role->id]);

        new DestroyRole(author: $author, role: $role)->execute();

        $this->assertModelMissing($role);
        $this->assertDatabaseMissing('role_permissions', ['role_id' => $role->id]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RoleDeletion
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === ['name' => 'Salesman'],
        );
    }

    #[Test]
    public function it_throws_when_somebody_still_holds_the_role(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->create(['company_id' => $company->id]);
        $dwight = User::factory()->create(['company_id' => $company->id]);
        $dwight->roles()->attach($role->id);

        new DestroyRole(author: $author, role: $role)->execute();
    }

    #[Test]
    public function it_throws_when_the_role_is_locked(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->locked()->create(['company_id' => $company->id]);

        new DestroyRole(author: $author, role: $role)->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_administer_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $member = User::factory()->create(['company_id' => $company->id]);
        $role = Role::factory()->create(['company_id' => $company->id]);

        new DestroyRole(author: $member, role: $role)->execute();
    }

    #[Test]
    public function it_throws_when_the_role_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->create(['company_id' => $michaelScottPaperCompany->id]);

        new DestroyRole(author: $author, role: $role)->execute();
    }
}
