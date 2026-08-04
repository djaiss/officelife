<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OccurrenceTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\User;

/**
 * Close an office of a company. The row stays, so what was written down about
 * the office is still there to read, and the list simply stops offering it.
 */
class ArchiveLocation
{
    public function __construct(
        private readonly User $author,
        private readonly Location $location,
    ) {}

    public function execute(): Location
    {
        $this->authorize();
        $this->archive();
        $this->publish();
        $this->log();

        return $this->location;
    }

    private function publish(): void
    {
        new PublishOccurrence(
            type: OccurrenceTypeEnum::LocationArchived,
            company: $this->location->company,
            subject: $this->location,
            actor: $this->author,
            payload: ['name' => $this->location->name],
        )->execute();
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->location->company)
            ->authorize();
    }

    /**
     * A closed office cannot be the head office, so the flag goes with it and
     * the company is left to promote another one.
     */
    private function archive(): void
    {
        $this->location->archived_at = now();
        $this->location->is_primary = false;
        $this->location->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->location->company,
            user: $this->author,
            action: UserActionEnum::LocationArchive,
            parameters: ['name' => $this->location->name],
        )->onQueue('low');
    }
}
