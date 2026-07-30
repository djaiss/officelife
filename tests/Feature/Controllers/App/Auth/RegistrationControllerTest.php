<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The password rules check the address against the Have I Been Pwned
        // range API, which the test suite has no business calling.
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);
    }

    #[Test]
    public function it_registers_a_company_with_its_first_user(): void
    {
        Queue::fake();
        Event::fake([Registered::class]);

        $response = $this->post('/register', [
            'company_name' => 'Dunder Mifflin',
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'thatswhatshesaid',
            'password_confirmation' => 'thatswhatshesaid',
            'terms' => 'on',
        ]);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('companies', ['name' => 'Dunder Mifflin']);
        $this->assertDatabaseHas('employees', [
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
        $this->assertDatabaseHas('users', ['email' => 'michael.scott@dundermifflin.com']);

        $user = User::query()->where('email', 'michael.scott@dundermifflin.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertEquals($user->id, $user->company->owner_user_id);
        $this->assertEquals('Michael Scott', $user->employee->name);

        Event::assertDispatched(Registered::class);
    }

    #[Test]
    public function it_requires_every_field(): void
    {
        $response = $this->post('/register', []);

        $response->assertSessionHasErrors([
            'company_name',
            'first_name',
            'last_name',
            'email',
            'password',
            'terms',
        ]);
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_an_email_that_is_already_taken(): void
    {
        Queue::fake();

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post('/register', [
            'company_name' => 'Dunder Mifflin',
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'thatswhatshesaid',
            'password_confirmation' => 'thatswhatshesaid',
            'terms' => 'on',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_password_that_is_not_confirmed(): void
    {
        $response = $this->post('/register', [
            'company_name' => 'Dunder Mifflin',
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'thatswhatshesaid',
            'password_confirmation' => 'bearsbeatsbattlestar',
            'terms' => 'on',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_disposable_email_address(): void
    {
        $response = $this->post('/register', [
            'company_name' => 'Dunder Mifflin',
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@mailinator.com',
            'password' => 'thatswhatshesaid',
            'password_confirmation' => 'thatswhatshesaid',
            'terms' => 'on',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
