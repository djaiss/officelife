<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EmailTypeEnum;
use App\Jobs\SendEmail;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Support\Facades\URL;

/**
 * Send the email that asks somebody to confirm the address they signed up
 * with. The link is signed and expires.
 */
class SendEmailVerification
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): User
    {
        $this->send();

        return $this->user;
    }

    private function send(): void
    {
        SendEmail::dispatch(
            mailable: new VerifyEmailMail(url: $this->url()),
            company: $this->user->company,
            emailType: EmailTypeEnum::EmailVerification,
            user: $this->user,
        )->onQueue('high');
    }

    private function url(): string
    {
        return URL::temporarySignedRoute(
            'auth.verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $this->user->id,
                'hash' => sha1($this->user->email),
            ],
        );
    }
}
