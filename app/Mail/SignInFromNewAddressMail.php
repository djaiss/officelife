<?php

declare(strict_types=1);

namespace App\Mail;

use App\Interfaces\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SignInFromNewAddressMail extends Mailable implements HasEnvelope
{
    public function __construct(
        public readonly string $email,
        public readonly string $ip,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('A sign-in from a new place on your :app account', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.sign-in-from-new-address',
            with: [
                'email' => $this->email,
                'ip' => $this->ip,
            ],
        );
    }
}
