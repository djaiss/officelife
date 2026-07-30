<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($user->company()->exists());
        $this->assertEquals('Dunder Mifflin', $user->company->name);
    }

    #[Test]
    public function it_returns_the_password_as_the_authentication_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('dwightschrute')]);

        $this->assertEquals($user->password, $user->getAuthPassword());
        $this->assertTrue(Hash::check('dwightschrute', $user->getAuthPassword()));
    }

    #[Test]
    public function it_returns_an_empty_authentication_password_when_the_user_signs_in_through_sso(): void
    {
        $user = User::factory()->singleSignOn()->create();

        $this->assertEquals('', $user->getAuthPassword());
    }

    #[Test]
    public function it_knows_whether_it_uses_single_sign_on(): void
    {
        $withSso = User::factory()->singleSignOn()->create();
        $withPassword = User::factory()->create();

        $this->assertTrue($withSso->usesSingleSignOn());
        $this->assertFalse($withPassword->usesSingleSignOn());
    }

    #[Test]
    public function it_hides_the_password_when_serialized(): void
    {
        $user = User::factory()->create();

        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    #[Test]
    public function it_casts_the_dates_and_the_active_flag(): void
    {
        $user = User::factory()->inactive()->create(['last_login_at' => now()]);

        $user->refresh();

        $this->assertFalse($user->is_active);
        $this->assertNotNull($user->last_login_at);
        $this->assertNotNull($user->email_verified_at);
    }

    #[Test]
    public function it_soft_deletes(): void
    {
        $user = User::factory()->create();

        $user->delete();

        $this->assertSoftDeleted($user);
    }
}
