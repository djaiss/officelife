<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\TextSanitizer;
use App\Models\Company;
use App\Models\Employee;

/**
 * Create an employee of a company.
 */
class CreateEmployee
{
    private Employee $employee;

    public function __construct(
        private readonly Company $company,
        private string $firstName,
        private string $lastName,
    ) {}

    public function execute(): Employee
    {
        $this->sanitize();
        $this->create();

        return $this->employee;
    }

    private function sanitize(): void
    {
        $this->firstName = TextSanitizer::plainText($this->firstName);
        $this->lastName = TextSanitizer::plainText($this->lastName);
    }

    private function create(): void
    {
        $this->employee = Employee::query()->create([
            'company_id' => $this->company->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
        ]);
    }
}
