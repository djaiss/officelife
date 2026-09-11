<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Security;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\HtmlString;

class TwoFactorEnrolmentViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?Employee $employee,
        private readonly string $secret,
        private readonly string $qrCode,
    ) {}

    public function qrCode(): HtmlString
    {
        return new HtmlString($this->qrCode);
    }

    public function secret(): string
    {
        return mb_trim(chunk_split($this->secret, 4, ' '));
    }

    public function email(): string
    {
        return $this->user->email;
    }

    public function name(): string
    {
        return $this->employee->name ?? $this->user->email;
    }

    public function employee(): ?Employee
    {
        return $this->employee;
    }

    public function companyName(): string
    {
        return $this->user->company->name;
    }
}
