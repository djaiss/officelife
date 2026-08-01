<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete a role. A role somebody still holds cannot be deleted: taking a set of
 * permissions away from people has to be something somebody meant to do, not
 * something that happens on the way to tidying up a list.
 */
class DestroyRole
{
    private string $name;

    public function __construct(
        private readonly User $author,
        private readonly Role $role,
    ) {}

    public function execute(): void
    {
        $this->authorize();
        $this->validate();
        $this->destroy();
        $this->log();
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
        if (! $this->role->is_editable) {
            throw new ModelNotFoundException('Role not found');
        }

        if ($this->role->users()->exists()) {
            throw new ModelNotFoundException('Role not found');
        }
    }

    /**
     * The name is kept before the row goes, so the log can still say which role
     * was deleted. The permissions of the role go with it, through the foreign
     * key.
     */
    private function destroy(): void
    {
        $this->name = $this->role->name;

        $this->role->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->role->company,
            user: $this->author,
            action: UserActionEnum::RoleDeletion,
            parameters: ['name' => $this->name],
        )->onQueue('low');
    }
}
