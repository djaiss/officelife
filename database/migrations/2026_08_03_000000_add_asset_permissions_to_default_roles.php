<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The assets module brings three permissions with it, and a company created
 * before it existed has default roles that know nothing about them. Roles are
 * created by an action rather than by a migration, so this is the only way to
 * reach the companies that already exist.
 *
 * The roles are editable, so a company that has changed its own is left alone
 * where it would conflict: the grants are inserted, never overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->grant(Role::ADMINISTRATOR, [
            PermissionEnum::AssetView,
            PermissionEnum::AssetManage,
            PermissionEnum::AssetCheckout,
        ]);

        $this->grant(Role::MEMBER, [PermissionEnum::AssetView]);

        $this->createItAdministrators();
    }

    public function down(): void
    {
        DB::table('role_permissions')
            ->whereIn('permission', [
                PermissionEnum::AssetView->value,
                PermissionEnum::AssetManage->value,
                PermissionEnum::AssetCheckout->value,
            ])
            ->delete();

        DB::table('roles')->where('slug', Role::IT_ADMINISTRATOR)->delete();
    }

    /**
     * @param  list<PermissionEnum>  $permissions
     */
    private function grant(string $slug, array $permissions): void
    {
        $roles = DB::table('roles')->where('slug', $slug)->pluck('id');

        foreach ($roles as $roleId) {
            foreach ($permissions as $permission) {
                $held = DB::table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission', $permission->value)
                    ->exists();

                if ($held) {
                    continue;
                }

                DB::table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission' => $permission->value,
                    'scope' => ScopeEnum::Company->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Give every company the role that looks after the equipment, unless it
     * already has one under that slug.
     */
    private function createItAdministrators(): void
    {
        $companies = DB::table('companies')
            ->whereNotIn('id', DB::table('roles')->where('slug', Role::IT_ADMINISTRATOR)->pluck('company_id'))
            ->pluck('id');

        foreach ($companies as $companyId) {
            $roleId = DB::table('roles')->insertGetId([
                'company_id' => $companyId,
                'name' => 'IT administrator',
                'slug' => Role::IT_ADMINISTRATOR,
                'is_default' => true,
                'is_editable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ([
                PermissionEnum::EmployeeView,
                PermissionEnum::AssetView,
                PermissionEnum::AssetManage,
                PermissionEnum::AssetCheckout,
            ] as $permission) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission' => $permission->value,
                    'scope' => ScopeEnum::Company->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
