<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Profile;

use App\Enums\PermissionEnum;
use App\Models\Employee;
use App\Models\User;

/**
 * What the profile screen shows: who is signed in, the record they are editing,
 * and the shell around it.
 */
class ProfileViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?Employee $employee,
    ) {}

    /**
     * The fields of the details box.
     *
     * @return array{first_name: string|null, last_name: string|null, display_name: string|null, work_email: string|null}
     */
    public function details(): array
    {
        return [
            'first_name' => old('first_name', $this->employee?->first_name),
            'last_name' => old('last_name', $this->employee?->last_name),
            'display_name' => old('display_name', $this->employee?->display_name),
            'work_email' => old('work_email', $this->employee?->work_email),
        ];
    }

    /**
     * The fields of the emergency contact box. They are private, so they only
     * leave here when whoever is looking is allowed to see them. Somebody who
     * may see a profile does not thereby get everything on it.
     *
     * @return array{name: string|null, phone: string|null, relationship: string|null}
     */
    public function emergencyContact(): array
    {
        if (! $this->canSeePrivateInformation()) {
            return [
                'name' => null,
                'phone' => null,
                'relationship' => null,
            ];
        }

        return [
            'name' => old('name', $this->employee?->emergency_contact_name),
            'phone' => old('phone', $this->employee?->emergency_contact_phone),
            'relationship' => old('relationship', $this->employee?->emergency_contact_relationship),
        ];
    }

    /**
     * Get whether the private details of the employee may be shown, so the
     * screen can leave the box out rather than show it empty.
     */
    public function canSeePrivateInformation(): bool
    {
        if ($this->employee === null) {
            return false;
        }

        return $this->user
            ->permission(PermissionEnum::EmployeeViewPrivate)
            ->forEmployee($this->employee)
            ->allowed();
    }

    /**
     * The name to show and to draw initials from. Somebody whose account is not
     * attached to an employee record has only an email address to go by.
     */
    public function name(): string
    {
        return $this->employee->name ?? $this->user->email;
    }

    /**
     * The record the avatar draws from, so the screen can show the photo when
     * there is one. An account that belongs to nobody who works here has none.
     */
    public function employee(): ?Employee
    {
        return $this->employee;
    }

    public function hasPhoto(): bool
    {
        return $this->employee?->hasPhoto() ?? false;
    }

    public function email(): string
    {
        return $this->user->email;
    }

    /**
     * Whether the sidebar shows the administration section, which is the way to
     * the roles of the company.
     */
    public function canManageRoles(): bool
    {
        return $this->user
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($this->user->company)
            ->allowed();
    }

    public function companyName(): string
    {
        return $this->user->company->name;
    }

    /**
     * How long ago the record was last saved, in words, or null when it never
     * has been, so the screen can leave the line out entirely.
     */
    public function lastSavedAt(): ?string
    {
        return $this->employee?->last_saved_at?->diffForHumans();
    }
}
