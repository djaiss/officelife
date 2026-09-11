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
use Illuminate\Support\Arr;

/**
 * What the two roles screens show: every role of the company as a list, and one
 * role on a screen of its own, with what it is allowed to do under one tab and
 * who holds it under the other.
 *
 * The list screen is built with no role, so the role is nullable and everything
 * about one role answers with nothing when there is none.
 */
class RolesViewModel
{
    /**
     * The roles of the company, asked for once and kept. The list reads them and
     * the counts above it read them again, and the second ask would be another
     * query.
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
        private readonly ?Employee $employee = null,
        private readonly ?Role $role = null,
        private readonly bool $onPeopleTab = false,
    ) {}

    public function companyName(): string
    {
        return $this->user->company->name;
    }

    /**
     * The name to show and to draw initials from. Somebody whose account is not
     * attached to an employee record has only an email address to go by.
     */
    public function name(): string
    {
        return $this->employee->name ?? $this->user->email;
    }

    /**
     * The record the avatar draws from, so the top bar can show it when
     * there is one. An account that belongs to nobody who works here has none.
     */
    public function employee(): ?Employee
    {
        return $this->employee;
    }

    public function createUrl(): string
    {
        return route('settings.roles.create');
    }

    /**
     * Every role of the company, one to a row: what it covers, how much of
     * everything on offer it grants, and how many people hold it.
     *
     * @return array<int, array{id: int, name: string, url: string, summary: string, permissions: string, holders: string, badges: array<int, array{label: string, tone: string}>, hue: int}>
     */
    public function rows(): array
    {
        return $this->roles()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'url' => route('settings.roles.show', $role->id),
                'summary' => $this->summary($role),
                /* The noun agrees with the total rather than with the count, and
                   there are ten permissions to grant, so the wording never
                   changes with the number in front of it. */
                'permissions' => trans_choice('[0,*]:count of :total permissions', $role->permissions_count, [
                    'total' => count(PermissionEnum::cases()),
                ]),
                'holders' => $this->holderLabel($role->users_count),
                'badges' => $this->badges($role),
                'hue' => $this->hue($role),
            ])
            ->all();
    }

    /**
     * The role being looked at, or null on the list screen.
     *
     * @return array{id: int, name: string, slug: string, isEditable: bool, badges: array<int, array{label: string, tone: string}>, hue: int, updateUrl: string, destroyUrl: string, duplicateUrl: string, assignUrl: string}|null
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
            'badges' => $this->badges($this->role),
            'hue' => $this->hue($this->role),
            'updateUrl' => route('settings.roles.update', $this->role->id),
            'destroyUrl' => route('settings.roles.destroy', $this->role->id),
            'duplicateUrl' => route('settings.roleDuplicates.create', $this->role->id),
            'assignUrl' => route('settings.rolePeople.create', $this->role->id),
        ];
    }

    /**
     * The two halves of a role, each a path of its own so either can be linked
     * to and gone back to.
     *
     * @return array<int, array{label: string, url: string, current: bool}>
     */
    public function tabs(): array
    {
        if ($this->role === null) {
            return [];
        }

        return [
            [
                'label' => trans_choice('[0,*]Permissions · :count', count($this->grants())),
                'url' => route('settings.roles.show', $this->role->id),
                'current' => ! $this->onPeopleTab,
            ],
            [
                'label' => trans_choice('[0,*]People · :count', $this->role->users()->count()),
                'url' => route('settings.roles.show', [$this->role->id, 'people']),
                'current' => $this->onPeopleTab,
            ],
        ];
    }

    public function onPeopleTab(): bool
    {
        return $this->onPeopleTab;
    }

    /**
     * The permission matrix, one section per group. A permission that covers the
     * whole company has nothing to narrow down, so it comes with no scopes at
     * all and the row says so instead of showing a toggle leading nowhere.
     *
     * What was submitted wins over what is stored, so a save turned away by the
     * validator gives the ticks back rather than throwing the edit away.
     *
     * @return array<int, array{title: string, note: string, count: string, width: string, tone: string, permissions: array<int, array{value: string, label: string, granted: bool, scope: string, targetsEmployee: bool, scopes: array<string, string>}>}>
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

            $granted = count(array_filter($permissions, fn (array $permission): bool => $permission['granted']));
            $ratio = $granted / count($permissions);

            $groups[] = [
                'title' => __($group->label()),
                'note' => __($group->note()),
                'count' => trans_choice('[0,*]:count of :total granted', $granted, ['total' => count($permissions)]),
                'width' => $granted === 0 ? '9px' : round($ratio * 100).'%',
                'tone' => match (true) {
                    $granted === 0 => 'none',
                    $ratio < 1 => 'partial',
                    default => 'full',
                },
                'permissions' => $permissions,
            ];
        }

        return $groups;
    }

    /**
     * The line at the right of the matrix header, saying how much of everything
     * on offer the role actually grants.
     */
    public function grantCountLabel(): string
    {
        return trans_choice('[0,*]:count of :total granted', count($this->grants()), [
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
     * Who holds the role, with the month they were given it and the way to take
     * it back.
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
                'since' => __('Holds it since :date', ['date' => $heldSince[$user->id]->isoFormat('MMM YYYY')]),
                'removeUrl' => route('settings.rolePeople.destroy', [$this->role->id, $user->id]),
            ])
            ->all();
    }

    /**
     * The colleagues who do not hold the role yet, for the dialog that hands it
     * out.
     *
     * @return array<int, array{id: int, name: string, email: string, employee: Employee|null}>
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
                'employee' => $user->employee,
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
            return trans_choice('[0,*]held by :count', $holders);
        }

        return null;
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
     * One row of the matrix.
     *
     * @return array{value: string, label: string, granted: bool, scope: string, targetsEmployee: bool, scopes: array<string, string>}
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

        $scopes = [];

        foreach ($permission->scopes() as $candidate) {
            $scopes[$candidate->value] = __($candidate->label());
        }

        return [
            'value' => $permission->value,
            'label' => __($permission->label()),
            'granted' => $granted,
            'scope' => $scope,
            'targetsEmployee' => $permission->targetsEmployee(),
            'scopes' => $permission->targetsEmployee() ? $scopes : [],
        ];
    }

    /**
     * @return Collection<int, Role>
     */
    private function roles(): Collection
    {
        return $this->roles ??= $this->user->company->roles()
            ->with('permissions')
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

        return $this->grants = $this->role->permissions
            ->mapWithKeys(fn (RolePermission $grant): array => [$grant->permission->value => $grant->scope])
            ->all();
    }

    /**
     * The sentence under the name of a role in the list. Roles carry no
     * description of their own, so it is read off what they grant: the sections
     * of the matrix they reach into, which is the shortest true thing that can
     * be said about a role without listing it out.
     */
    private function summary(Role $role): string
    {
        if ($role->permissions_count === 0) {
            return __('Nothing yet. Tick what it is allowed to do.');
        }

        if ($role->permissions_count === count(PermissionEnum::cases())) {
            return __('Everything, including handing out roles.');
        }

        $granted = $role->permissions
            ->map(fn (RolePermission $grant): PermissionGroupEnum => $grant->permission->group())
            ->unique();

        $groups = array_map(
            fn (PermissionGroupEnum $group): string => __($group->label()),
            array_filter(
                PermissionGroupEnum::cases(),
                fn (PermissionGroupEnum $group): bool => $granted->contains($group),
            ),
        );

        return Arr::join(array_values($groups), ', ', ' '.__('and').' ');
    }

    /**
     * How many people hold a role. Nobody holding it is its own sentence rather
     * than a count of none, since that is the thing worth noticing about it.
     */
    private function holderLabel(int $holders): string
    {
        if ($holders === 0) {
            return __('nobody');
        }

        return trans_choice(':count person|:count people', $holders);
    }

    /**
     * What is worth saying about a role beside its name: that it amounts to full
     * access, and that the application looks after it itself.
     *
     * @return array<int, array{label: string, tone: string}>
     */
    private function badges(Role $role): array
    {
        $badges = [];

        if ($this->administers($role)) {
            $badges[] = ['label' => __('Full access'), 'tone' => 'accent'];
        }

        if (! $role->is_editable) {
            $badges[] = ['label' => __('Not editable'), 'tone' => 'neutral'];
        }

        return $badges;
    }

    /**
     * The colour of the square beside a role, which sets a role granting the
     * administration of the company apart from the rest at a glance.
     */
    private function hue(Role $role): int
    {
        return $this->administers($role) ? 30 : 80;
    }

    private function administers(Role $role): bool
    {
        return $role->permissions
            ->contains(fn (RolePermission $grant): bool => $grant->permission === PermissionEnum::RoleManage);
    }
}
