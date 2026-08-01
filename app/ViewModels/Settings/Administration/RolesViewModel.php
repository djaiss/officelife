<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Administration;

use App\Enums\PermissionEnum;
use App\Enums\PermissionGroupEnum;
use App\Enums\ScopeEnum;
use App\Models\Employee;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * What the roles screen shows: every role of the company down the left, and the
 * one being looked at on the right, with what it is allowed to do and who holds
 * it.
 *
 * A company that has deleted every one of its roles has none to look at, which
 * is why the selected role is nullable and the screen has a blank state.
 */
class RolesViewModel
{
    /**
     * The roles of the company, asked for once and kept. Both columns of the
     * screen read them, and the second ask would be another query.
     *
     * @var Collection<int, Role>|null
     */
    private ?Collection $roles = null;

    /**
     * What the selected role grants, keyed by permission, so a row of the matrix
     * is one array lookup rather than one query.
     *
     * @var array<string, ScopeEnum>|null
     */
    private ?array $grants = null;

    public function __construct(
        private readonly User $user,
        private readonly ?Role $role,
    ) {}

    /**
     * The line above the list, which says how many roles there are rather than
     * making somebody count the rows.
     */
    public function rolesHeader(): string
    {
        return __('Roles · :count', ['count' => $this->roles()->count()]);
    }

    /**
     * Every role of the company, with the line under its name saying how much it
     * grants and how many people hold it.
     *
     * @return array<int, array{id: int, name: string, url: string, summary: string, selected: bool, isEditable: bool}>
     */
    public function list(): array
    {
        return $this->roles()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'url' => route('settings.roles.show', $role->id),
                'summary' => $this->summary($role),
                'selected' => $role->id === $this->role?->id,
                'isEditable' => $role->is_editable,
            ])
            ->all();
    }

    /**
     * The role being looked at, or null when the company has none left.
     *
     * @return array{id: int, name: string, slug: string, isEditable: bool, updateUrl: string, destroyUrl: string, duplicateUrl: string, assignUrl: string}|null
     */
    public function role(): ?array
    {
        if ($this->role === null) {
            return null;
        }

        return [
            'id' => $this->role->id,
            'name' => old('name', $this->role->name),
            'slug' => $this->role->slug,
            'isEditable' => $this->role->is_editable,
            'updateUrl' => route('settings.roles.update', $this->role->id),
            'destroyUrl' => route('settings.roles.destroy', $this->role->id),
            'duplicateUrl' => route('settings.roleDuplicates.create', $this->role->id),
            'assignUrl' => route('settings.rolePeople.create', $this->role->id),
        ];
    }

    /**
     * The permission matrix, one section per group. A permission that covers the
     * whole company has nothing to narrow down, so it comes with no scopes at
     * all and the row says so instead of showing a picker leading nowhere.
     *
     * What was submitted wins over what is stored, so a save turned away by the
     * validator gives the ticks back rather than throwing the edit away.
     *
     * @return array<int, array{title: string, note: string, count: string, permissions: array<int, array{value: string, label: string, granted: bool, scope: string, targetsEmployee: bool, scopes: array<int, array{value: string, label: string}>}>}>
     */
    public function groups(): array
    {
        $groups = [];

        foreach (PermissionGroupEnum::cases() as $group) {
            $permissions = array_values(array_map(
                fn (PermissionEnum $permission): array => $this->permission($permission),
                array_filter(
                    PermissionEnum::cases(),
                    fn (PermissionEnum $permission): bool => $permission->group() === $group,
                ),
            ));

            $granted = array_filter($permissions, fn (array $permission): bool => $permission['granted']);

            $groups[] = [
                'title' => __($group->label()),
                'note' => __($group->note()),
                'count' => __(':granted of :total', ['granted' => count($granted), 'total' => count($permissions)]),
                'permissions' => $permissions,
            ];
        }

        return $groups;
    }

    /**
     * The line under the matrix that says in full what the one word on each of
     * the scope buttons means.
     *
     * @return array<int, array{short: string, label: string}>
     */
    public function scopeLegend(): array
    {
        return array_map(
            fn (ScopeEnum $scope): array => ['short' => __($scope->shortLabel()), 'label' => __($scope->label())],
            ScopeEnum::cases(),
        );
    }

    /**
     * The line at the right of the matrix header, saying how much of everything
     * on offer the role actually grants.
     */
    public function grantCountLabel(): string
    {
        return __(':granted of :total granted', [
            'granted' => count($this->grants()),
            'total' => count(PermissionEnum::cases()),
        ]);
    }

    /**
     * Whether the role hands out the administration of the company, in which
     * case whoever holds it can grant themselves everything else and the screen
     * says as much.
     */
    public function warnsAboutAdministration(): bool
    {
        return array_key_exists(PermissionEnum::RoleManage->value, $this->grants());
    }

    /**
     * Who holds the role, with the day they were given it and the way to take it
     * back.
     *
     * @return array<int, array{id: int, name: string, email: string, employee: Employee|null, since: string, removeUrl: string}>
     */
    public function people(): array
    {
        if ($this->role === null) {
            return [];
        }

        $heldSince = $this->heldSince($this->role);

        return $this->role->users()
            ->with('employee')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->employee->name ?? $user->email,
                'email' => $user->email,
                'employee' => $user->employee,
                'since' => __('since :date', ['date' => $heldSince[$user->id]->isoFormat('MMM YYYY')]),
                'removeUrl' => route('settings.rolePeople.destroy', [$this->role->id, $user->id]),
            ])
            ->all();
    }

    /**
     * The day each holder of the role was given it, keyed by who they are. It is
     * read off the row that joins the two rather than through the relation,
     * since the day a role was handed out belongs to neither of them.
     *
     * @return array<int, Carbon>
     */
    private function heldSince(Role $role): array
    {
        return UserRole::query()
            ->where('role_id', $role->id)
            ->get()
            ->mapWithKeys(fn (UserRole $held): array => [$held->user_id => $held->created_at])
            ->all();
    }

    /**
     * The colleagues who do not hold the role yet, for the panel that hands it
     * out.
     *
     * @return array<int, array{id: int, name: string, email: string}>
     */
    public function assignable(): array
    {
        if ($this->role === null) {
            return [];
        }

        return $this->user->company->users()
            ->whereDoesntHave('roles', fn ($query) => $query->where('roles.id', $this->role->id))
            ->with('employee')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->employee->name ?? $user->email,
                'email' => $user->email,
            ])
            ->all();
    }

    /**
     * Whether the role can go. A role somebody still holds cannot, and neither
     * can one the application looks after itself.
     */
    public function canBeDeleted(): bool
    {
        return $this->deleteHint() === null;
    }

    /**
     * Why the role cannot go, to be shown beside the entry that would delete it,
     * or null when nothing is in the way.
     */
    public function deleteHint(): ?string
    {
        if ($this->role === null || ! $this->role->is_editable) {
            return __('not editable');
        }

        $holders = $this->role->users()->count();

        if ($holders > 0) {
            return __('held by :count', ['count' => $holders]);
        }

        return null;
    }

    /**
     * What a new role can start from: nothing at all, or the permissions of a
     * role that already exists.
     *
     * @return array<int, array{id: string, name: string}>
     */
    public function templates(): array
    {
        $templates = [['id' => '', 'name' => __('Nothing')]];

        foreach ($this->roles() as $role) {
            $templates[] = ['id' => (string) $role->id, 'name' => $role->name];
        }

        return $templates;
    }

    /**
     * One row of the matrix.
     *
     * @return array{value: string, label: string, granted: bool, scope: string, targetsEmployee: bool, scopes: array<int, array{value: string, label: string}>}
     */
    private function permission(PermissionEnum $permission): array
    {
        $submitted = old('permissions');
        $grants = $this->grants();

        $granted = $submitted === null
            ? array_key_exists($permission->value, $grants)
            : isset($submitted[$permission->value]['granted']);

        $scope = $submitted === null
            ? ($grants[$permission->value] ?? ScopeEnum::Company)->value
            : ($submitted[$permission->value]['scope'] ?? ScopeEnum::Company->value);

        return [
            'value' => $permission->value,
            'label' => __($permission->label()),
            'granted' => $granted,
            'scope' => $scope,
            'targetsEmployee' => $permission->targetsEmployee(),
            'scopes' => $permission->targetsEmployee()
                ? array_map(
                    fn (ScopeEnum $scope): array => ['value' => $scope->value, 'label' => __($scope->shortLabel())],
                    $permission->scopes(),
                )
                : [],
        ];
    }

    /**
     * @return Collection<int, Role>
     */
    private function roles(): Collection
    {
        return $this->roles ??= $this->user->company->roles()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, ScopeEnum>
     */
    private function grants(): array
    {
        if ($this->grants !== null) {
            return $this->grants;
        }

        if ($this->role === null) {
            return $this->grants = [];
        }

        return $this->grants = $this->role->permissions()
            ->get()
            ->mapWithKeys(fn (RolePermission $grant): array => [$grant->permission->value => $grant->scope])
            ->all();
    }

    /**
     * The line under a role in the list: how much it grants, and how many people
     * hold it.
     */
    private function summary(Role $role): string
    {
        $permissions = $role->permissions_count === 1
            ? __('1 permission')
            : __(':count permissions', ['count' => $role->permissions_count]);

        $people = match ($role->users_count) {
            0 => __('nobody'),
            1 => __('1 person'),
            default => __(':count people', ['count' => $role->users_count]),
        };

        return $permissions.' · '.$people;
    }
}
