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
            self::CompanyManage => false,
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
        };
    }
}
