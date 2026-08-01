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
 * Give somebody a role. A role only ever reaches the people of the company it
 * belongs to, and giving somebody a role they already hold changes nothing.
 *
 * Owner does not go through here. It is derived from who created the company
 * and cannot be handed to anybody.
 */
class AssignRole
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
            $this->assign();
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

    private function assign(): void
    {
        $this->user->roles()->syncWithoutDetaching([$this->role->id]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->role->company,
            user: $this->author,
            action: UserActionEnum::RoleAssignment,
            parameters: [
                'name' => $this->role->name,
                'email' => $this->user->email,
            ],
        )->onQueue('low');
    }
}
