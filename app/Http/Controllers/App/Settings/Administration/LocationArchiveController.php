<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\ArchiveLocation;
use App\Actions\RestoreLocation;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Closing an office and reopening it are the two things that can be done to its
 * archive, which is why they live here rather than beside the fields.
 *
 * Both go back to the list they were asked from rather than to the list of open
 * offices, so reopening an office from the archived list leaves you on the
 * archived list, one office shorter.
 */
class LocationArchiveController extends Controller
{
    public function create(Request $request, int $location): RedirectResponse
    {
        new ArchiveLocation(
            author: $request->user(),
            location: $this->locations($request)->findOrFail($location),
        )->execute();

        return back()
            ->with('status', __('The office is archived.'))
            ->with('status_description', __('Nothing written about it is lost. It is on the archived list whenever you want it back.'));
    }

    public function destroy(Request $request, int $location): RedirectResponse
    {
        new RestoreLocation(
            author: $request->user(),
            location: $this->locations($request)->findOrFail($location),
        )->execute();

        return back()
            ->with('status', __('The office is open again.'))
            ->with('status_description', __('It comes back as an ordinary office. Promote it if it is the head office.'));
    }

    /**
     * @return HasMany<Location, Company>
     */
    private function locations(Request $request): HasMany
    {
        return $request->user()->company->locations();
    }
}
