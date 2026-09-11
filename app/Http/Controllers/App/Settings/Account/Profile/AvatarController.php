<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Profile;

use App\Actions\DestroyEmployeeAvatar;
use App\Actions\UpdateEmployeeAvatar;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AvatarController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $employee = $request->user()->employee;

        if ($employee === null) {
            abort(404);
        }

        new UpdateEmployeeAvatar(
            author: $request->user(),
            employee: $employee,
            file: $validated['avatar'],
        )->execute();

        return redirect()->route('settings.profile.index')
            ->with('status', __('Your avatar is saved.'))
            ->with('status_description', __('It may take a minute to appear everywhere.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;

        if ($employee === null) {
            abort(404);
        }

        new DestroyEmployeeAvatar(
            author: $request->user(),
            employee: $employee,
        )->execute();

        return redirect()->route('settings.profile.index')
            ->with('status', __('Your avatar is removed.'))
            ->with('status_description', __('We show your initials again.'));
    }

    public function show(Request $request, Employee $employee, int $size): StreamedResponse
    {
        $request->user()
            ->permission(PermissionEnum::EmployeeView)
            ->forEmployee($employee)
            ->authorize();

        if (! $employee->hasAvatar()) {
            abort(404);
        }

        // Only the sizes actually written to disk, so this cannot be turned
        // into an endpoint that resizes whatever it is asked for.
        if (! in_array($size, Employee::avatarPixelSizes(), true)) {
            abort(404);
        }

        $disk = Storage::disk((string) config('filesystems.default'));
        $path = $employee->avatarVariantPath($size);

        if (! $disk->exists($path)) {
            abort(404);
        }

        return $disk->response($path, headers: [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
