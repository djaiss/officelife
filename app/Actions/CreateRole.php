<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Create a role in a company, along with everything it is allowed to do. Only
 * somebody who may administer the company can do this.
 */
class CreateRole
{
    private Role $role;

    /**
     * @param  list<array{permission: PermissionEnum, scope: ScopeEnum}>  $grants
     */
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

    /**
     * A permission that covers the whole company has nothing to narrow down, so
     * asking for it at self scope is asking for something that cannot be
     * evaluated rather than for something narrower.
     */
    private function validate(): void
    {
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

    private function create(): void
    {
        $this->role = Role::query()->create([
            'company_id' => $this->company->id,
            'name' => $this->name,
            'slug' => $this->slug(),
            'is_default' => false,
            'is_editable' => true,
        ]);
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
            action: UserActionEnum::RoleCreation,
            parameters: [
                'name' => $this->role->name,
                'permissions' => implode(', ', array_map(
                    fn (array $grant): string => $grant['permission']->value.':'.$grant['scope']->value,
                    $this->grants,
                )),
            ],
        )->onQueue('low');
    }

    /**
     * Build a slug out of the role name, suffixed with a number when another
     * role of the same company already took it.
     */
    private function slug(): string
    {
        $base = Str::slug($this->name);
        $base = $base === '' ? 'role' : $base;

        $slug = $base;
        $suffix = 1;

        while (Role::query()->where('company_id', $this->company->id)->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }
}
