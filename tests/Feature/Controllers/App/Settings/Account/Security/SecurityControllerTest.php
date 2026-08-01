<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_security_screen(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'display_name' => null,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'password_changed_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Change password', escape: false);
        $response->assertSee('Current password', escape: false);
        $response->assertSee('Dwight Schrute', escape: false);
        $response->assertSee('Dunder Mifflin', escape: false);
        $response->assertSee('Last changed 2 days ago', escape: false);
    }

    #[Test]
    public function it_leaves_out_the_date_when_the_password_was_never_changed(): void
    {
        $user = User::factory()->create(['password_changed_at' => null]);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Last changed', escape: false);
    }

    #[Test]
    public function it_says_there_is_nothing_to_change_when_the_account_signs_in_elsewhere(): void
    {
        $user = User::factory()->singleSignOn()->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('You sign in through your identity provider, so there is no password to change here.', escape: false);
        $response->assertDontSee('Current password', escape: false);
    }

    #[Test]
    public function it_redirects_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('settings.security.index'));

        $response->assertRedirect(route('auth.login.new'));
    }
}
