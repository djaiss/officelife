<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Logs;

use App\Models\EmailSent;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;

class EmailsSentViewModel
{
    private const int PER_PAGE = 10;

    /** @var CursorPaginator<int, EmailSent>|null */
    private ?CursorPaginator $emailsSent = null;

    public function __construct(
        private readonly User $user,
        private readonly ?Employee $employee,
    ) {}

    /** @return CursorPaginator<int, EmailSent> */
    public function emailsSent(): CursorPaginator
    {
        return $this->emailsSent ??= $this->user->emailsSent()
            ->latest('sent_at')
            ->cursorPaginate(self::PER_PAGE);
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
