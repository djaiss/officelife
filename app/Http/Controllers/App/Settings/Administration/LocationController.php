<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\CreateLocation;
use App\Actions\UpdateLocation;
use App\Enums\LocationScopeEnum;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Location;
use App\ViewModels\Settings\Administration\LocationsViewModel;
use DateTimeZone;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationController extends Controller
{
    /**
     * Which offices are shown is the last segment of the path, so the open ones,
     * the closed ones and all of them are three pages rather than one page with
     * a filter hidden in a query string.
     */
    public function index(Request $request, ?string $scope = null): View
    {
        $this->authorize($request);

        return view('app.settings.administration.locations.index', [
            'viewModel' => new LocationsViewModel(
                user: $request->user(),
                scope: LocationScopeEnum::fromSegment($scope),
                search: trim((string) $request->query('q', '')),
                sort: $request->query('sort') === 'place' ? 'place' : 'name',
                direction: $request->query('dir') === 'desc' ? 'desc' : 'asc',
            ),
        ]);
    }

    /**
     * The dialog that asks for a new office sits on a screen that edits an
     * office of its own, and both have a field called `name`. Its messages
     * therefore go in a bag of their own, and that bag having anything in it is
     * what reopens the dialog.
     */
    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createLocation', [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
        ]);

        new CreateLocation(
            author: $request->user(),
            company: $request->user()->company,
            name: $validated['name'],
            country: $validated['country'] ?? null,
            city: $validated['city'] ?? null,
        )->execute();

        return redirect()->route('settings.locations.index')
            ->with('status', __('The office is added.'))
            ->with('status_description', __('Give it a time zone and an address whenever you have them.'));
    }

    /**
     * What is submitted replaces what the office had, so a field left empty is a
     * field emptied. The head office box only ever promotes: unticking it would
     * leave the company without one, so it is ignored.
     *
     * It goes back to the list it was saved from, which is what keeps a save
     * made while reading the archived offices on the archived offices.
     */
    public function update(Request $request, int $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'address' => ['nullable', 'string', 'max:65535'],
            'timezone' => ['nullable', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'is_primary' => ['nullable'],
        ]);

        new UpdateLocation(
            author: $request->user(),
            location: $this->locations($request)->findOrFail($location),
            name: $validated['name'],
            country: $validated['country'] ?? null,
            city: $validated['city'] ?? null,
            address: $validated['address'] ?? null,
            timezone: $validated['timezone'] ?? null,
            isPrimary: isset($validated['is_primary']),
        )->execute();

        return back()
            ->with('status', __('The office is saved.'))
            ->with('status_description', __('Everybody who works there reads the new details from now on.'));
    }

    /**
     * Reading the screen has no action behind it to ask on its behalf, so it
     * asks here.
     */
    private function authorize(Request $request): void
    {
        $request->user()
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($request->user()->company)
            ->authorize();
    }

    /**
     * The offices of the company of whoever is asking, which is what keeps an
     * office of another company out of reach.
     *
     * @return HasMany<Location, Company>
     */
    private function locations(Request $request): HasMany
    {
        return $request->user()->company->locations();
    }
}
