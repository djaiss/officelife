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

    public function create(Request $request): RedirectResponse
    {
        // Its own error bag: the dialog and the screen behind it both have a
        // field called `name`.
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

    private function authorize(Request $request): void
    {
        $request->user()
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($request->user()->company)
            ->authorize();
    }

    /** @return HasMany<Office, Company> */
    private function offices(Request $request): HasMany
    {
        return $request->user()->company->offices();
    }
}
