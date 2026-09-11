<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

class SignInViewModel extends GuestViewModel
{
    /**
     * The address the sign in shortcut signs in, or null when there is no
     * shortcut to offer. It only ever exists on a machine somebody develops on,
     * so the screen has nothing to draw anywhere else.
     */
    public function localSignInEmail(): ?string
    {
        if (! app()->environment('local')) {
            return null;
        }

        return (string) config('officelife.seed_email');
    }
}
