<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\CreateRole;
use App\Actions\DestroyRole;
use App\Actions\UpdateRole;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Role;
use App\ViewModels\Settings\Administration\RolesViewModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize($request);

        return view('app.settings.administration.roles.index', [
            'viewModel' => new RolesViewModel(
                user: $request->user(),
                employee: $request->user()->employee,
            ),
        ]);
    }

    /**
     * What a role is allowed to do and who holds it are two screens rather than
     * one screen with a switch on it, so either of them can be linked to and
     * gone back to. Which one is being read is the last segment of the path.
     */
    public function show(Request $request, int $role, ?string $tab = null): View
    {
        $this->authorize($request);

        return view('app.settings.administration.roles.show', [
            'viewModel' => new RolesViewModel(
                user: $request->user(),
                employee: $request->user()->employee,
                role: $this->roles($request)->findOrFail($role),
                onPeopleTab: $tab === 'people',
            ),
        ]);
    }

    /**
     * The dialog that asks for a new role sits on a screen with a field called
     * `name` of its own, so its messages go in a bag of their own. That bag
     * having anything in it is also what reopens the dialog.
     */
    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createRole', [
            'name' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $role = new CreateRole(
            author: $request->user(),
            company: $request->user()->company,
            name: $validated['name'],
            grants: [],
        )->execute();

        return redirect()->route('settings.roles.show', $role->id)
            ->with('status', __('The role is created.'))
            ->with('status_description', __('Nothing changes for anybody until you hand it out.'));
    }

    /**
     * What is submitted replaces what the role had, so a permission left
     * unticked is a permission taken away. A permission covering the whole
     * company has nothing to narrow down, so whatever scope the form carried for
     * it is ignored.
     */
    public function update(Request $request, int $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*.granted' => ['nullable'],
            'permissions.*.scope' => ['nullable', 'string', Rule::enum(ScopeEnum::class)],
        ]);

        $grants = [];

        foreach ($validated['permissions'] ?? [] as $value => $submitted) {
            $permission = PermissionEnum::tryFrom((string) $value);

            if ($permission === null || ! isset($submitted['granted'])) {
                continue;
            }

            $grants[] = [
                'permission' => $permission,
                'scope' => $permission->targetsEmployee()
                    ? ScopeEnum::from($submitted['scope'] ?? ScopeEnum::Company->value)
                    : ScopeEnum::Company,
            ];
        }

        new UpdateRole(
            author: $request->user(),
            role: $this->roles($request)->findOrFail($role),
            name: $validated['name'],
            grants: $grants,
        )->execute();

        return redirect()->route('settings.roles.show', $role)
            ->with('status', __('The role is saved.'))
            ->with('status_description', __('Everybody who holds it is covered by it from now on.'));
    }

    public function destroy(Request $request, int $role): RedirectResponse
    {
        new DestroyRole(
            author: $request->user(),
            role: $this->roles($request)->findOrFail($role),
        )->execute();

        return redirect()->route('settings.roles.index')
            ->with('status', __('The role is deleted.'))
            ->with('status_description', __('What it granted is granted by it no longer.'));
    }

    /**
     * Reading the screen has no action behind it to ask on its behalf, so it
     * asks here.
     */
    private function authorize(Request $request): void
    {
        $request->user()
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($request->user()->company)
            ->authorize();
    }

    /**
     * The roles of the company of whoever is asking, which is what keeps a role
     * of another company out of reach.
     *
     * @return HasMany<Role, Company>
     */
    private function roles(Request $request): HasMany
    {
        return $request->user()->company->roles();
    }
}
