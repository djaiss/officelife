<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateEmailSent;
use App\Enums\EmailTypeEnum;
use App\Interfaces\HasEnvelope;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Resend\Laravel\Facades\Resend;

class SendEmail implements ShouldQueue
{
    use Queueable;

    private string $subject;

    private string $recipient;

    private ?string $uuid = null;

    public function __construct(
        public Mailable&HasEnvelope $mailable,
        public Company $company,
        public EmailTypeEnum $emailType,
        public ?User $user = null,
        public ?string $emailAddress = null,
    ) {}

    public function handle(): void
    {
        $this->setRecipient();
        $this->setSubject();

        if (config('app.use_resend')) {
            $this->sendWithResend();
        } else {
            $this->sendTheTraditionalWay();
        }

        $this->recordEmailSent();
    }

    private function setRecipient(): void
    {
        $recipient = $this->emailAddress ?? $this->user?->email;

        if ($recipient === null) {
            throw new InvalidArgumentException('An email needs either a user or an email address.');
        }

        $this->recipient = $recipient;
    }

    private function setSubject(): void
    {
        $this->subject = $this->mailable->envelope()->subject;
    }

    private function sendWithResend(): void
    {
        $response = Resend::emails()->send([
            'from' => config('mail.from.address'),
            'to' => [$this->recipient],
            'subject' => $this->subject,
            'html' => $this->mailable->render(),
        ]);

        $this->uuid = $response->id;
    }

    private function sendTheTraditionalWay(): void
    {
        Mail::to($this->recipient)->send($this->mailable);
    }

    private function recordEmailSent(): void
    {
        new CreateEmailSent(
            company: $this->company,
            user: $this->user,
            uuid: $this->uuid,
            emailType: $this->emailType->value,
            emailAddress: $this->recipient,
            subject: $this->subject,
            body: $this->mailable->render(),
        )->execute();
    }
}
