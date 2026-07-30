<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\CreateMagicLink;
use App\Enums\EmailType;
use App\Http\Controllers\Controller;
use App\Jobs\SendEmail;
use App\Mail\MagicLinkCreated;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SendMagicLinkController extends Controller
{
    public function create(): View
    {
        return view('app.auth.request-magic-link');
    }

    public function store(Request $request): View
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = mb_strtolower((string) $request->input('email'));

        // An address nobody signed up with gets the same screen as one that did.
        // Telling the visitor otherwise would let them find out who has an
        // account here.
        try {
            $user = User::query()->where('email', $email)->firstOrFail();

            $link = new CreateMagicLink(
                email: $email,
            )->execute();

            SendEmail::dispatch(
                mailable: new MagicLinkCreated(
                    link: $link,
                ),
                company: $user->company,
                emailType: EmailType::MagicLinkCreated,
                user: $user,
            )->onQueue('high');
        } catch (ModelNotFoundException) {
        }

        return view('app.auth.magic-link-sent');
    }
}
