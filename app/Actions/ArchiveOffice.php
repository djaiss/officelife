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
 * Close an office of a company. The row stays, so what was written down about
 * the office is still there to read, and the list simply stops offering it.
 */
class ArchiveOffice
{
    public function __construct(
        private readonly User $author,
        private readonly Office $office,
    ) {}

    public function execute(): Office
    {
        $this->authorize();
        $this->archive();
        $this->publish();
        $this->log();

        return $this->office;
    }

    private function publish(): void
    {
        new PublishOccurrence(
            type: OccurrenceTypeEnum::OfficeArchived,
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

    private function archive(): void
    {
        $this->office->archived_at = now();
        $this->office->is_head_office = false;
        $this->office->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->office->company,
            user: $this->author,
            action: UserActionEnum::OfficeArchived,
            parameters: ['name' => $this->office->name],
        )->onQueue('low');
    }
}
