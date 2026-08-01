<?php

declare(strict_types=1);

namespace Tests;

use App\Actions\CreateDefaultRoles;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    /**
     * Give somebody a role granting one permission, and nothing else. A user
     * built by the factory holds no role and may therefore do nothing, so a
     * test that is not about permissions still has to say what the person it is
     * about is allowed to do.
     */
    protected function grant(User $user, PermissionEnum $permission, ScopeEnum $scope = ScopeEnum::Company): User
    {
        $role = Role::query()->create([
            'company_id' => $user->company_id,
            'name' => $permission->value.' at '.$scope->value.' scope',
            'slug' => Str::slug($permission->value.'-'.$scope->value),
            'is_default' => false,
            'is_editable' => true,
        ]);

        RolePermission::query()->create([
            'role_id' => $role->id,
            'permission' => $permission,
            'scope' => $scope,
        ]);

        $user->roles()->attach($role->id);

        return $user;
    }

    /**
     * Give somebody the role every colleague gets, creating the default roles
     * of their company when it has none. The screens are built for people who
     * hold it, so a feature test has to hand it out before it can reach one.
     */
    protected function makeMember(User $user): User
    {
        $company = $user->company;

        if (! $company->roles()->exists()) {
            new CreateDefaultRoles(company: $company)->execute();
        }

        $member = $company->roles()->where('slug', Role::MEMBER)->firstOrFail();

        $user->roles()->attach($member->id);

        return $user;
    }
}
