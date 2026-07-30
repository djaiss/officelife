<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);
    }

    #[Test]
    public function it_sends_a_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->from(route('password.request'))->post('/forgot-password', [
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function it_says_the_same_thing_when_the_email_is_unknown(): void
    {
        Notification::fake();

        $response = $this->from(route('password.request'))->post('/forgot-password', [
            'email' => 'nobody@dundermifflin.com',
        ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('status');
        $response->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_resets_the_password(): void
    {
        $user = User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'password' => Hash::make('thatswhatshesaid'),
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'bearsbeatsbattlestar',
            'password_confirmation' => 'bearsbeatsbattlestar',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $this->assertTrue(Hash::check('bearsbeatsbattlestar', $user->refresh()->password));
    }

    #[Test]
    public function it_refuses_an_invalid_token(): void
    {
        User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'password' => Hash::make('thatswhatshesaid'),
        ]);

        $response = $this->post('/reset-password', [
            'token' => 'this-token-is-made-up',
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'bearsbeatsbattlestar',
            'password_confirmation' => 'bearsbeatsbattlestar',
        ]);

        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function it_requires_a_confirmed_password(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post('/reset-password', [
            'token' => Password::createToken($user),
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'bearsbeatsbattlestar',
            'password_confirmation' => 'thatswhatshesaid',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
