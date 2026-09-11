<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OccurrenceTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Office;
use App\Models\User;

/**
 * Reopen an office the company had closed. It comes back as an ordinary office
 * rather than as the head office, since being closed is what took that away.
 */
class RestoreOffice
{
    public function __construct(
        private readonly User $author,
        private readonly Office $office,
    ) {}

    public function execute(): Office
    {
        $this->authorize();
        $this->restore();
        $this->publish();
        $this->log();

        return $this->office;
    }

    private function publish(): void
    {
        new PublishOccurrence(
            type: OccurrenceTypeEnum::OfficeReopened,
            company: $this->office->company,
            subject: $this->office,
            actor: $this->author,
            payload: ['name' => $this->office->name],
        )->execute();
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->office->company)
            ->authorize();
    }

    private function restore(): void
    {
        $this->office->archived_at = null;
        $this->office->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->office->company,
            user: $this->author,
            action: UserActionEnum::OfficeRestored,
            parameters: ['name' => $this->office->name],
        )->onQueue('low');
    }
}
