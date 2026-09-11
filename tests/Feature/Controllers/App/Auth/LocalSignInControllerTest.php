<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalSignInControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hides_the_shortcut_outside_the_local_environment(): void
    {
        $response = $this->get(route('auth.signIn.new'));

        $response->assertStatus(200);
        $response->assertDontSee('Sign in as michael.scott@dundermifflin.com');
    }

    #[Test]
    public function it_shows_the_shortcut_in_the_local_environment(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');

        $response = $this->get(route('auth.signIn.new'));

        $response->assertStatus(200);
        $response->assertSee('Sign in as michael.scott@dundermifflin.com');
    }

    #[Test]
    public function it_signs_the_seeded_account_in(): void
    {
        Queue::fake();

        $this->app->detectEnvironment(fn (): string => 'local');
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.localSignIn.create'));

        $response->assertRedirect(route('settings.profile.index'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_refuses_to_sign_anybody_in_outside_the_local_environment(): void
    {
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.localSignIn.create'));

        $response->assertStatus(404);
        $this->assertGuest();
    }

    #[Test]
    public function it_reports_a_database_that_was_never_seeded(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');
        $this->withoutMiddleware(PreventRequestForgery::class);

        $response = $this->post(route('auth.localSignIn.create'));

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function it_sends_a_signed_in_visitor_away(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('auth.localSignIn.create'));

        $response->assertRedirect(route('home.index'));
    }
}
