<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\AttemptSignIn;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sign the seeded account in without typing anything, so that developing does
 * not mean filling the same form all day.
 *
 * It goes through the same action as the real sign in screen, with the password
 * the seeder used, rather than reaching for the guard itself. There is then one
 * way to be signed in here, and this shortcut cannot drift away from it.
 */
class LocalSignInController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        abort_unless(app()->environment('local'), 404);

        new AttemptSignIn(
            email: (string) config('officelife.seed_email'),
            password: (string) config('officelife.seed_password'),
            ip: $request->ip(),
        )->execute();

        $request->session()->regenerate();

        return redirect()->route('settings.profile.index');
    }
}
