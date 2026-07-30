<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\CreateCompany;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Turnstile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('app.auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'company_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:'.User::class,
                'disposable_email',
            ],
            'password' => [
                'required',
                'string',
                'max:255',
                'confirmed',
                Password::min(8)->uncompromised(),
            ],
            'terms' => ['accepted'],
        ];

        if (config('turnstile.enabled')) {
            $rules['cf-turnstile-response'] = ['required', new Turnstile];
        }

        $validated = $request->validate($rules, [
            'terms.accepted' => __('You have to agree with the terms of use and the privacy policy to create an account.'),
        ]);

        $company = new CreateCompany(
            name: $validated['company_name'],
            email: mb_strtolower((string) $validated['email']),
            password: $validated['password'],
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
        )->execute();

        $user = $company->owner;

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard.index', absolute: false));
    }
}
