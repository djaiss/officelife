<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailVerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_the_verification_link_again(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post('/verify-email');

        $response->assertRedirect(route('verification.notice'));
        $response->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    #[Test]
    public function it_does_not_send_anything_to_a_verified_user(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/verify-email');

        $response->assertRedirect('/dashboard');

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_sends_a_verified_user_straight_to_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/verify-email')->assertRedirect('/dashboard');
    }

    #[Test]
    public function it_verifies_the_email_address(): void
    {
        Event::fake([Verified::class]);

        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertRedirect('/dashboard?verified=1');
        $this->assertTrue($user->refresh()->hasVerifiedEmail());

        Event::assertDispatched(Verified::class);
    }

    #[Test]
    public function it_refuses_a_link_that_was_not_signed(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(
            route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]),
        );

        $response->assertForbidden();
        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    #[Test]
    public function it_requires_a_signed_in_user(): void
    {
        $this->post('/verify-email')->assertRedirect(route('login'));
    }
}
