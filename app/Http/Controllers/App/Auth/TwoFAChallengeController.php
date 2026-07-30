<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\VerifyTwoFactorCode;
use App\Http\Controllers\Controller;
use App\Jobs\CheckLastLogin;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFAChallengeController extends Controller
{
    public function create(): View
    {
        if (! session('2fa:user:id')) {
            return view('app.auth.2fa', [
                'error' => __('Session expired. Please login again.'),
            ]);
        }

        return view('app.auth.2fa');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()->find(session('2fa:user:id'));

        // The challenge lives in the session, and the session can expire between
        // the password and the code. Without this the user we look up is null.
        if ($user === null) {
            return to_route('login')->withErrors([
                'email' => __('Session expired. Please login again.'),
            ]);
        }

        if (! new VerifyTwoFactorCode(
            user: $user,
            code: (string) $request->input('code'),
        )->execute()) {
            return back()->withErrors(['code' => __('Invalid code')]);
        }

        Auth::login($user);
        session()->forget('2fa:user:id');
        $request->session()->regenerate();

        CheckLastLogin::dispatch(
            user: $request->user(),
            ip: $request->ip(),
        )->onQueue('low');

        return redirect()->intended(route('dashboard.index', absolute: false));
    }
}
