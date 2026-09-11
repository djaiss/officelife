<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\CreateOffice;
use App\Actions\UpdateOffice;
use App\Enums\OfficeScopeEnum;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Office;
use App\ViewModels\Settings\Administration\OfficesViewModel;
use DateTimeZone;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OfficeController extends Controller
{
    /**
     * Which offices are shown is the last segment of the path, so the open ones,
     * the closed ones and all of them are three pages rather than one page with
     * a filter hidden in a query string.
     */
    public function index(Request $request, ?string $scope = null): View
    {
        $this->authorize($request);

        return view('app.settings.administration.offices.index', [
            'viewModel' => new OfficesViewModel(
                user: $request->user(),
                employee: $request->user()->employee,
                scope: OfficeScopeEnum::fromSegment($scope),
                search: trim((string) $request->query('q', '')),
                sort: $request->query('sort') === 'place' ? 'place' : 'name',
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
        $validated = $request->validateWithBag('createOffice', [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
        ]);

        new CreateOffice(
            author: $request->user(),
            company: $request->user()->company,
            name: $validated['name'],
            country: $validated['country'] ?? null,
            city: $validated['city'] ?? null,
        )->execute();

        return redirect()->route('settings.offices.index')
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
    public function update(Request $request, int $office): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'address' => ['nullable', 'string', 'max:65535'],
            'timezone' => ['nullable', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'is_head_office' => ['nullable'],
        ]);

        new UpdateOffice(
            author: $request->user(),
            office: $this->offices($request)->findOrFail($office),
            name: $validated['name'],
            country: $validated['country'] ?? null,
            city: $validated['city'] ?? null,
            address: $validated['address'] ?? null,
            timezone: $validated['timezone'] ?? null,
            isHeadOffice: isset($validated['is_head_office']),
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
     * @return HasMany<Office, Company>
     */
    private function offices(Request $request): HasMany
    {
        return $request->user()->company->offices();
    }
}
