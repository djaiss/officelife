<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\UserActionEnum;
use App\Helpers\Slug;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Create a role in a company, along with everything it is allowed to do. Only
 * somebody who may administer the company can do this.
 */
class CreateRole
{
    private Role $role;

    /** @param  list<array{permission: PermissionEnum, scope: ScopeEnum}>  $grants */
    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private string $name,
        private readonly array $grants = [],
    ) {}

    public function execute(): Role
    {
        $this->authorize();
        $this->validate();
        $this->sanitize();

        DB::transaction(function (): void {
            $this->create();
            $this->grant();
        });

        $this->log();

        return $this->role;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($this->company)
            ->authorize();
    }

    private function validate(): void
    {
        $seen = [];

        foreach ($this->grants as $grant) {
            if (! in_array($grant['scope'], $grant['permission']->scopes(), true)) {
                throw new InvalidArgumentException($grant['permission']->value.' cannot be granted at '.$grant['scope']->value.' scope');
            }

            if (in_array($grant['permission'], $seen, true)) {
                throw new InvalidArgumentException($grant['permission']->value.' is granted more than once');
            }

            $seen[] = $grant['permission'];
        }
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
    }

    private function create(): void
    {
        $this->role = Slug::write(
            name: $this->name,
            fallback: 'role',
            taken: fn (string $slug): bool => $this->company->roles()->where('slug', $slug)->exists(),
            write: fn (string $slug): Role => Role::query()->create([
                'company_id' => $this->company->id,
                'name' => $this->name,
                'slug' => $slug,
                'is_default' => false,
                'is_editable' => true,
            ]),
        );
    }

    private function grant(): void
    {
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
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::RoleCreated,
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
