<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The screens themselves are placeholders, so these only check that each one is
 * reachable and renders. What they look like comes later.
 */
class AuthScreenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_the_screens_a_visitor_can_reach(): void
    {
        $this->get('/register')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/send-magic-link')->assertOk();
        $this->get('/forgot-password')->assertOk();
        $this->get('/2fa-challenge')->assertOk();
    }

    #[Test]
    public function it_renders_the_reset_password_screen(): void
    {
        $user = User::factory()->create();

        $this->get(route('password.reset', ['token' => Password::createToken($user)]))->assertOk();
    }

    #[Test]
    public function it_renders_the_email_verification_screen(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/verify-email')->assertOk();
    }

    #[Test]
    public function it_keeps_a_signed_in_user_away_from_the_guest_screens(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect();
        $this->actingAs($user)->get('/register')->assertRedirect();
    }
}
