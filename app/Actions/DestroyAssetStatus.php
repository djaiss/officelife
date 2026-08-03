<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\AssetStatus;
use App\Models\User;
use InvalidArgumentException;

/**
 * Remove a status a company added. Refused while any equipment is in it, since
 * that equipment would be left in a state that no longer exists, and refused
 * outright for the statuses every company gets.
 */
class DestroyAssetStatus
{
    public function __construct(
        private readonly User $author,
        private readonly AssetStatus $status,
    ) {}

    public function execute(): bool
    {
        $this->authorize();
        $this->validate();
        $this->log();
        $this->destroy();

        return true;
    }

    private function authorize(): void
    {
        if ($this->status->company === null) {
            throw new InvalidArgumentException('A status every company gets cannot be deleted');
        }

        $this->author
            ->permission(PermissionEnum::AssetManage)
            ->forCompany($this->status->company)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->status->is_system) {
            throw new InvalidArgumentException('A status every company gets cannot be deleted');
        }

        if ($this->status->assets()->exists()) {
            throw new InvalidArgumentException('Equipment is still in that state');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->status->company,
            user: $this->author,
            action: UserActionEnum::AssetStatusDeletion,
            parameters: ['name' => $this->status->name],
        )->onQueue('low');
    }

    private function destroy(): void
    {
        $this->status->delete();
    }
}
