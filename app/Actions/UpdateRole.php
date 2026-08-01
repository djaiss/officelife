<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Rename a role and say afresh what it is allowed to do. What is passed in
 * replaces what the role had, so a permission left out is a permission taken
 * away.
 *
 * The slug is left alone, since it is what the role is known by elsewhere.
 */
class UpdateRole
{
    /**
     * @param  list<array{permission: PermissionEnum, scope: ScopeEnum}>  $grants
     */
    public function __construct(
        private readonly User $author,
        private readonly Role $role,
        private string $name,
        private readonly array $grants = [],
    ) {}

    public function execute(): Role
    {
        $this->authorize();
        $this->validate();
        $this->sanitize();

        DB::transaction(function (): void {
            $this->update();
            $this->regrant();
        });

        $this->log();

        return $this->role;
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

        foreach ($this->grants as $grant) {
            if (! in_array($grant['scope'], $grant['permission']->scopes(), true)) {
                throw new InvalidArgumentException($grant['permission']->value.' cannot be granted at '.$grant['scope']->value.' scope');
            }
        }
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
    }

    private function update(): void
    {
        $this->role->name = $this->name;
        $this->role->save();
    }

    private function regrant(): void
    {
        $this->role->permissions()->delete();

        foreach ($this->grants as $grant) {
            RolePermission::query()->create([
                'role_id' => $this->role->id,
                'permission' => $grant['permission'],
                'scope' => $grant['scope'],
            ]);
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->role->company,
            user: $this->author,
            action: UserActionEnum::RoleUpdate,
            parameters: [
                'name' => $this->role->name,
                'permissions' => implode(', ', array_map(
                    fn (array $grant): string => $grant['permission']->value.':'.$grant['scope']->value,
                    $this->grants,
                )),
            ],
        )->onQueue('low');
    }
}
