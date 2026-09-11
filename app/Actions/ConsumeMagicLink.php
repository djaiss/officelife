<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EmailTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\DetectSignInAddressChange;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Mail\MagicLinkSignInMail;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Spend a magic link and sign its owner in. The link burns on first use.
 */
class ConsumeMagicLink
{
    private MagicLink $magicLink;

    private User $user;

    public function __construct(
        private readonly string $token,
        private readonly ?string $ip = null,
    ) {}

    public function execute(): User
    {
        $this->validate();
        $this->burn();
        $this->stamp();
        $this->notify();
        $this->log();

        return $this->user;
    }

    private function validate(): void
    {
        $magicLink = MagicLink::query()
            ->with('user')
            ->where('token', hash('sha256', $this->token))
            ->first();

        if ($magicLink === null || ! $magicLink->isUsable() || ! $magicLink->user->is_active) {
            throw new ModelNotFoundException('Magic link not found');
        }

        $this->magicLink = $magicLink;
        $this->user = $magicLink->user;
    }

    private function burn(): void
    {
        $this->magicLink->used_at = now();
        $this->magicLink->save();
    }

    private function stamp(): void
    {
        $this->user->last_signed_in_at = now();
        $this->user->save();

        DetectSignInAddressChange::dispatch(
            user: $this->user,
            ip: $this->ip ?? '',
        )->onQueue('low');
    }

    private function notify(): void
    {
        SendEmail::dispatch(
            mailable: new MagicLinkSignInMail(ip: $this->ip ?? ''),
            company: $this->user->company,
            emailType: EmailTypeEnum::MagicLinkSignIn,
            user: $this->user,
        )->onQueue('high');
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::UserSignedIn,
        )->onQueue('low');
    }
}
