<?php

declare(strict_types=1);

namespace App\Mail;

use App\Interfaces\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SignInFailedMail extends Mailable implements HasEnvelope
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Failed sign-in attempt on your :app account', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.sign-in-failed',
        );
    }
}
