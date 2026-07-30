<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Jobs\CheckLastLogin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFAChallengeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_the_user_in_with_a_valid_code(): void
    {
        Queue::fake();

        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->withSession(['2fa:user:id' => $user->id])
            ->post('/2fa-challenge', [
                'code' => $google2fa->getCurrentOtp($secret),
            ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('2fa:user:id'));

        Queue::assertPushedOn(queue: 'low', job: CheckLastLogin::class);
    }

    #[Test]
    public function it_signs_the_user_in_with_a_recovery_code(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'two_factor_secret' => 'ADUMQRSTUVWXYZ234567',
            'two_factor_recovery_codes' => ['dunder-1111', 'mifflin-2222'],
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->withSession(['2fa:user:id' => $user->id])
            ->post('/2fa-challenge', ['code' => 'dunder-1111']);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // The code has been burnt.
        $this->assertEquals(['mifflin-2222'], $user->refresh()->two_factor_recovery_codes);
    }

    #[Test]
    public function it_refuses_an_invalid_code(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'ADUMQRSTUVWXYZ234567',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->withSession(['2fa:user:id' => $user->id])
            ->post('/2fa-challenge', ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    #[Test]
    public function it_sends_the_visitor_back_to_login_when_the_session_expired(): void
    {
        $response = $this->post('/2fa-challenge', ['code' => '000000']);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    #[Test]
    public function it_requires_a_code(): void
    {
        $user = User::factory()->create();

        $this->withSession(['2fa:user:id' => $user->id])
            ->post('/2fa-challenge', [])
            ->assertSessionHasErrors('code');
    }
}
