<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Employee;
use App\Models\User;

/**
 * Update who to call about an employee when something happens to them. This is
 * private information, so being allowed to edit somebody's profile is not
 * enough on its own to change it.
 *
 * The details themselves are not logged, only that they changed. Who somebody
 * lives with is not something to leave lying in a log.
 */
class UpdateEmergencyContact
{
    public function __construct(
        private readonly User $author,
        private readonly Employee $employee,
        private ?string $name = null,
        private ?string $phone = null,
        private ?string $relationship = null,
    ) {}

    public function execute(): Employee
    {
        $this->authorize();
        $this->sanitize();
        $this->update();
        $this->log();

        return $this->employee;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::EmployeeUpdatePrivate)
            ->forEmployee($this->employee)
            ->authorize();
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::nullablePlainText($this->name);
        $this->phone = TextSanitizer::nullablePlainText($this->phone);
        $this->relationship = TextSanitizer::nullablePlainText($this->relationship);
    }

    private function update(): void
    {
        $this->employee->emergency_contact_name = $this->name;
        $this->employee->emergency_contact_phone = $this->phone;
        $this->employee->emergency_contact_relationship = $this->relationship;
        $this->employee->last_saved_at = now();
        $this->employee->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->author->company,
            user: $this->author,
            action: UserActionEnum::EmergencyContactUpdate,
        )->onQueue('low');
    }
}
