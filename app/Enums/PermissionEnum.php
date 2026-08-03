<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Everything a role can be allowed to do. There is no permissions table: this
 * enum is the list, and a role stores the value of the case it grants. Renaming
 * a value therefore needs a data migration.
 *
 * A permission is only added along with the feature that uses it.
 */
enum PermissionEnum: string
{
    case EmployeeView = 'employee.view';
    case EmployeeCreate = 'employee.create';
    case EmployeeUpdate = 'employee.update';
    case EmployeeViewPrivate = 'employee.view_private';
    case EmployeeUpdatePrivate = 'employee.update_private';
    case RoleManage = 'role.manage';
    case CompanyManage = 'company.manage';
    case AssetView = 'asset.view';
    case AssetManage = 'asset.manage';
    case AssetCheckout = 'asset.checkout';

    /**
     * Whether the permission is about one employee at a time, and therefore
     * needs an employee to be checked against. The ones that are not cover the
     * whole company and are always granted at company scope.
     */
    public function targetsEmployee(): bool
    {
        return match ($this) {
            self::EmployeeView,
            self::EmployeeUpdate,
            self::EmployeeViewPrivate,
            self::EmployeeUpdatePrivate => true,
            self::EmployeeCreate,
            self::RoleManage,
            self::CompanyManage,
            self::AssetView,
            self::AssetManage,
            self::AssetCheckout => false,
        };
    }

    /**
     * The module the permission belongs to, or null when it belongs to the core.
     * A permission of a module the company has not turned on is denied, whatever
     * a role grants, which is the only part of the module boundary somebody can
     * observe.
     *
     * The permissions of every module live in this enum rather than in one of
     * their own. That is a deliberate simplification while there is a single
     * module: composing several enums means replacing every match in this file
     * with an interface, for no gain the user can see. It is revisited when a
     * second module arrives.
     */
    public function module(): ?ModuleEnum
    {
        return match ($this) {
            self::AssetView,
            self::AssetManage,
            self::AssetCheckout => ModuleEnum::Assets,
            self::EmployeeView,
            self::EmployeeCreate,
            self::EmployeeUpdate,
            self::EmployeeViewPrivate,
            self::EmployeeUpdatePrivate,
            self::RoleManage,
            self::CompanyManage => null,
        };
    }

    /**
     * The scopes a role may grant the permission at. A permission with no
     * employee target has nothing to narrow down, so it only ever comes at
     * company scope and no scope is offered for it.
     *
     * @return list<ScopeEnum>
     */
    public function scopes(): array
    {
        if (! $this->targetsEmployee()) {
            return [ScopeEnum::Company];
        }

        return [ScopeEnum::Self, ScopeEnum::Company];
    }

    /**
     * The section the permission is listed under on the screen where somebody
     * grants them. Every permission belongs to exactly one.
     */
    public function group(): PermissionGroupEnum
    {
        return match ($this) {
            self::EmployeeView,
            self::EmployeeCreate,
            self::EmployeeUpdate => PermissionGroupEnum::People,
            self::EmployeeViewPrivate,
            self::EmployeeUpdatePrivate => PermissionGroupEnum::SensitiveData,
            self::RoleManage,
            self::CompanyManage => PermissionGroupEnum::Administration,
            self::AssetView,
            self::AssetManage,
            self::AssetCheckout => PermissionGroupEnum::Assets,
        };
    }

    /**
     * What the permission is called on the screen where somebody grants it. The
     * sentence doubles as the translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::EmployeeView => 'See the profile of a colleague',
            self::EmployeeCreate => 'Add somebody to the company',
            self::EmployeeUpdate => 'Change the profile of a colleague',
            self::EmployeeViewPrivate => 'See the private details of a colleague',
            self::EmployeeUpdatePrivate => 'Change the private details of a colleague',
            self::RoleManage => 'Administer the company, its roles and who holds them',
            self::CompanyManage => 'Change the settings of the company',
            self::AssetView => 'See the equipment the company owns',
            self::AssetManage => 'Add, change and archive equipment and its catalogue',
            self::AssetCheckout => 'Hand equipment out and take it back',
        };
    }
}
