<?php

declare(strict_types=1);

namespace App\Mail;

use App\Interfaces\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MagicLinkSignInMail extends Mailable implements HasEnvelope
{
    public function __construct(
        public readonly string $ip,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('You signed in to :app without a password', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.magic-link-sign-in',
            with: ['ip' => $this->ip],
        );
    }
}
