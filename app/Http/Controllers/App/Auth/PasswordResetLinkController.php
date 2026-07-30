<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Http\Controllers\Controller;
use App\Rules\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('app.auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];

        if (config('turnstile.enabled')) {
            $rules['cf-turnstile-response'] = ['required', new Turnstile];
        }

        $request->validate($rules);

        Password::sendResetLink($request->only('email'));

        return back()->with('status', __('A reset link will be sent if the account exists.'));
    }
}
