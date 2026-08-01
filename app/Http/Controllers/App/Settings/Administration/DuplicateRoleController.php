<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\CreateRole;
use App\Http\Controllers\Controller;
use App\Models\RolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Copy a role, permissions and all, so a company that wants something close to
 * one of its roles starts from it rather than from an empty matrix.
 *
 * The copy is a role like any other: editable, held by nobody, and given a free
 * slug by the action that creates it.
 */
class DuplicateRoleController extends Controller
{
    public function create(Request $request, int $role): RedirectResponse
    {
        $original = $request->user()->company->roles()->findOrFail($role);

        $copy = new CreateRole(
            author: $request->user(),
            company: $request->user()->company,
            name: __(':name (copy)', ['name' => $original->name]),
            grants: $original->permissions()
                ->get()
                ->map(fn (RolePermission $grant): array => ['permission' => $grant->permission, 'scope' => $grant->scope])
                ->all(),
        )->execute();

        return redirect()->route('settings.roles.show', $copy->id)
            ->with('status', __('The role is copied.'))
            ->with('status_description', __('It grants the same as the one it came from, and nobody holds it.'));
    }
}
