<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DomainEventTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Services\DomainEvents;
use Carbon\Carbon;

/**
 * Create an employee of a company. The information the employee keeps private
 * is not set here, as they fill it in themselves once they have an account.
 */
class CreateEmployee
{
    private Employee $employee;

    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private string $firstName,
        private string $lastName,
        private ?string $employeeNumber = null,
        private ?string $displayName = null,
        private ?string $workEmail = null,
        private ?string $customTitle = null,
        private ?string $country = null,
        private ?string $timezone = null,
        private readonly ?Carbon $hiredAt = null,
    ) {}

    public function execute(): Employee
    {
        $this->authorize();
        $this->sanitize();
        $this->create();
        $this->publish();
        $this->log();

        return $this->employee;
    }

    /**
     * An employee record being created is not somebody arriving. The events for
     * arriving and leaving are published from the lifecycle status, once that
     * exists, and this one only says that a record was written.
     */
    private function publish(): void
    {
        DomainEvents::publish(
            type: DomainEventTypeEnum::EmployeeCreated,
            company: $this->company,
            subject: $this->employee,
            actor: $this->author,
            payload: ['name' => $this->employee->name],
        );
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::EmployeeCreate)
            ->forCompany($this->company)
            ->authorize();
    }

    private function sanitize(): void
    {
        $this->firstName = TextSanitizer::plainText($this->firstName);
        $this->lastName = TextSanitizer::plainText($this->lastName);
        $this->employeeNumber = TextSanitizer::nullablePlainText($this->employeeNumber);
        $this->displayName = TextSanitizer::nullablePlainText($this->displayName);
        $this->customTitle = TextSanitizer::nullablePlainText($this->customTitle);
        $this->timezone = TextSanitizer::nullablePlainText($this->timezone);

        $workEmail = TextSanitizer::nullablePlainText($this->workEmail);
        $this->workEmail = $workEmail === null ? null : mb_strtolower($workEmail);

        $country = TextSanitizer::nullablePlainText($this->country);
        $this->country = $country === null ? null : mb_strtoupper($country);
    }

    private function create(): void
    {
        $this->employee = Employee::query()->create([
            'company_id' => $this->company->id,
            'employee_number' => $this->employeeNumber,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'display_name' => $this->displayName,
            'work_email' => $this->workEmail,
            'custom_title' => $this->customTitle,
            'country' => $this->country,
            'timezone' => $this->timezone,
            'hired_at' => $this->hiredAt,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::EmployeeCreation,
            parameters: ['name' => $this->employee->name],
        )->onQueue('low');
    }
}
