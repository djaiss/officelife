<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\ArchiveOffice;
use App\Actions\RestoreOffice;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Office;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OfficeArchiveController extends Controller
{
    public function create(Request $request, int $office): RedirectResponse
    {
        new ArchiveOffice(
            author: $request->user(),
            office: $this->offices($request)->findOrFail($office),
        )->execute();

        return back()
            ->with('status', __('The office is archived.'))
            ->with('status_description', __('Nothing written about it is lost. It is on the archived list whenever you want it back.'));
    }

    public function destroy(Request $request, int $office): RedirectResponse
    {
        new RestoreOffice(
            author: $request->user(),
            office: $this->offices($request)->findOrFail($office),
        )->execute();

        return back()
            ->with('status', __('The office is open again.'))
            ->with('status_description', __('It comes back as an ordinary office. Promote it if it is the head office.'));
    }

    /** @return HasMany<Office, Company> */
    private function offices(Request $request): HasMany
    {
        return $request->user()->company->offices();
    }
}
