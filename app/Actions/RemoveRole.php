<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Take a role away from somebody. Taking away a role they never held changes
 * nothing.
 *
 * The owner of a company keeps every permission whatever happens here, since
 * that comes from owning the company rather than from a role.
 */
class RemoveRole
{
    public function __construct(
        private readonly User $author,
        private readonly User $user,
        private readonly Role $role,
    ) {}

    public function execute(): User
    {
        $this->authorize();
        $this->validate();

        DB::transaction(function (): void {
            $this->remove();
        });

        $this->log();

        return $this->user;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($this->role->company)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->user->company_id !== $this->role->company_id) {
            throw new ModelNotFoundException('User not found');
        }
    }

    private function remove(): void
    {
        $this->user->roles()->detach($this->role->id);
        $this->user->forgetGrants();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->role->company,
            user: $this->author,
            action: UserActionEnum::RoleRemoval,
            parameters: [
                'name' => $this->role->name,
                'email' => $this->user->email,
            ],
        )->onQueue('low');
    }
}
