<?php

declare(strict_types=1);

namespace App\Permissions;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use InvalidArgumentException;

/**
 * A permission somebody is being checked for, waiting to be told what it is
 * being checked against. It is what User::permission() hands back:
 *
 *     $user->permission(PermissionEnum::EmployeeUpdate)
 *         ->forEmployee($employee)
 *         ->authorize();
 *
 * The target is always passed in and never worked out from the user, so a check
 * cannot quietly answer about something other than what is being acted on.
 */
class PendingPermissionCheck
{
    public function __construct(
        private readonly User $user,
        private readonly PermissionEnum $permission,
    ) {}

    /**
     * Answer the check against one employee.
     */
    public function forEmployee(Employee $employee): PermissionDecision
    {
        if (! $this->permission->targetsEmployee()) {
            throw new InvalidArgumentException($this->permission->value.' covers the whole company and has to be checked with forCompany()');
        }

        // Tenant isolation comes before anything else, the owner bypass
        // included: a permission never reaches outside the company of whoever
        // holds it.
        if ($this->user->company_id !== $employee->company_id) {
            return new PermissionDecision(false);
        }

        if ($this->ownsTheCompany()) {
            return new PermissionDecision(true);
        }

        $scopes = $this->grantedScopes();

        if (in_array(ScopeEnum::Company, $scopes, true)) {
            return new PermissionDecision(true);
        }

        if (in_array(ScopeEnum::Self, $scopes, true)) {
            // Somebody whose account belongs to nobody who works here has no
            // record of their own, so self covers nothing at all for them.
            return new PermissionDecision(
                $this->user->employee_id !== null && $this->user->employee_id === $employee->id,
            );
        }

        return new PermissionDecision(false);
    }

    /**
     * Answer the check against one company.
     */
    public function forCompany(Company $company): PermissionDecision
    {
        if ($this->permission->targetsEmployee()) {
            throw new InvalidArgumentException($this->permission->value.' is about one employee and has to be checked with forEmployee()');
        }

        if ($this->user->company_id !== $company->id) {
            return new PermissionDecision(false);
        }

        if ($this->ownsTheCompany()) {
            return new PermissionDecision(true);
        }

        return new PermissionDecision(in_array(ScopeEnum::Company, $this->grantedScopes(), true));
    }

    /**
     * Get whether the user owns the company the target belongs to. The company
     * of the user is the company of the target by the time this is asked, since
     * a check that got this far already compared the two.
     *
     * An owner may do everything inside their own company. That is not a role
     * and cannot be granted or taken away.
     */
    private function ownsTheCompany(): bool
    {
        return $this->user->company->owner_user_id === $this->user->id;
    }

    /**
     * Get the scopes the roles of the user grant the permission at, across all
     * of their roles at once.
     *
     * @return list<ScopeEnum>
     */
    private function grantedScopes(): array
    {
        return $this->user->grants()[$this->permission->value] ?? [];
    }
}
