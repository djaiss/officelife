<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Enums\EmailType;
use App\Jobs\SendEmail;
use App\Mail\MagicLinkCreated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendMagicLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_a_magic_link(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post('/send-magic-link', [
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('magic_links', 1);

        Queue::assertPushedOn(
            queue: 'high',
            job: SendEmail::class,
            callback: fn (SendEmail $job): bool => $job->mailable instanceof MagicLinkCreated
                && $job->emailType === EmailType::MagicLinkCreated
                && $job->user->id === $user->id
                && $job->company->id === $user->company_id,
        );
    }

    #[Test]
    public function it_says_the_same_thing_when_the_email_is_unknown(): void
    {
        Queue::fake();

        $response = $this->post('/send-magic-link', [
            'email' => 'nobody@dundermifflin.com',
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('magic_links', 0);

        Queue::assertNotPushed(SendEmail::class);
    }

    #[Test]
    public function it_requires_an_email(): void
    {
        $this->post('/send-magic-link', [])->assertSessionHasErrors('email');
    }
}
