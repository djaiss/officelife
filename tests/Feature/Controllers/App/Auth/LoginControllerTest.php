<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Enums\EmailType;
use App\Jobs\CheckLastLogin;
use App\Jobs\SendEmail;
use App\Mail\LoginFailed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_the_user_in(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'password' => Hash::make('thatswhatshesaid'),
        ]);

        $response = $this->post('/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'thatswhatshesaid',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        Queue::assertPushedOn(
            queue: 'low',
            job: CheckLastLogin::class,
            callback: fn (CheckLastLogin $job): bool => $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_refuses_the_wrong_password_and_warns_the_user(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'password' => Hash::make('thatswhatshesaid'),
        ]);

        $response = $this->post('/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'bearsbeatsbattlestar',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        Queue::assertPushedOn(
            queue: 'high',
            job: SendEmail::class,
            callback: fn (SendEmail $job): bool => $job->mailable instanceof LoginFailed
                && $job->emailType === EmailType::LoginFailed
                && $job->user->id === $user->id
                && $job->company->id === $user->company_id,
        );
    }

    #[Test]
    public function it_does_not_warn_anybody_when_the_email_is_unknown(): void
    {
        Queue::fake();

        $this->post('/login', [
            'email' => 'nobody@dundermifflin.com',
            'password' => 'thatswhatshesaid',
        ])->assertSessionHasErrors('email');

        Queue::assertNotPushed(SendEmail::class);
    }

    #[Test]
    public function it_refuses_a_suspended_user(): void
    {
        Queue::fake();

        User::factory()->inactive()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'password' => Hash::make('thatswhatshesaid'),
        ]);

        $response = $this->post('/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'thatswhatshesaid',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function it_sends_a_user_with_two_factor_authentication_to_the_challenge(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'password' => Hash::make('thatswhatshesaid'),
            'two_factor_secret' => 'ADUMQRSTUVWXYZ234567',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'thatswhatshesaid',
        ]);

        $response->assertRedirect(route('2fa.challenge.create'));
        $this->assertGuest();
        $this->assertEquals($user->id, session('2fa:user:id'));
    }

    #[Test]
    public function it_stops_after_five_failed_attempts(): void
    {
        Queue::fake();

        User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'password' => Hash::make('thatswhatshesaid'),
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', [
                'email' => 'michael.scott@dundermifflin.com',
                'password' => 'bearsbeatsbattlestar',
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'thatswhatshesaid',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        RateLimiter::clear(mb_strtolower('michael.scott@dundermifflin.com').'|127.0.0.1');
    }

    #[Test]
    public function it_requires_an_email_and_a_password(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
    }

    #[Test]
    public function it_signs_the_user_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
