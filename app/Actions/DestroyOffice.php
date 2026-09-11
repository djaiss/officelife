<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Office;
use App\Models\User;

/**
 * Delete an office of a company.
 */
class DestroyOffice
{
    private string $name;

    public function __construct(
        private readonly User $author,
        private readonly Office $office,
    ) {}

    public function execute(): void
    {
        $this->authorize();
        $this->destroy();
        $this->log();
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->office->company)
            ->authorize();
    }

    private function destroy(): void
    {
        $this->name = $this->office->name;

        $this->office->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->office->company,
            user: $this->author,
            action: UserActionEnum::OfficeDeleted,
            parameters: ['name' => $this->name],
        )->onQueue('low');
    }
}
