<?php

declare(strict_types=1);

namespace App\Permissions;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class EmployeeScope
{
    /** @return Builder<Employee> */
    public static function for(User $user, PermissionEnum $permission): Builder
    {
        if (! $permission->targetsEmployee()) {
            throw new InvalidArgumentException($permission->value.' covers the whole company and has no list of employees to narrow down');
        }

        // Every employee query starts inside the company of whoever is asking.
        $query = Employee::query()->where('company_id', $user->company_id);

        if ($user->company->owner_user_id === $user->id) {
            return $query;
        }

        $scopes = $user->grants()[$permission->value] ?? [];

        if (in_array(ScopeEnum::Company, $scopes, true)) {
            return $query;
        }

        if (in_array(ScopeEnum::Self, $scopes, true) && $user->employee_id !== null) {
            return $query->where('id', $user->employee_id);
        }

        return $query->whereIn('id', []);
    }
}
