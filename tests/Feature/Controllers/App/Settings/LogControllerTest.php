<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings;

use App\Enums\UserActionEnum;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_logs_screen(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'display_name' => null,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        Log::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'action' => UserActionEnum::CompanyUpdate->value,
            'parameters' => ['name' => 'Dunder Mifflin'],
        ]);

        $response = $this->actingAs($user)->get(route('settings.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Michael Scott', escape: false);
        $response->assertSee('company_updated', escape: false);
        $response->assertSee('Updated the company called Dunder Mifflin', escape: false);
    }

    #[Test]
    public function it_shows_a_word_when_there_is_nothing_to_read_yet(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Nothing yet. Your actions show up here as you go.', escape: false);
    }

    #[Test]
    public function it_hides_the_logs_of_somebody_else(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $colleague = User::factory()->create(['company_id' => $company->id]);

        Log::factory()->create([
            'company_id' => $company->id,
            'user_id' => $colleague->id,
            'action' => UserActionEnum::CompanyUpdate->value,
            'parameters' => ['name' => 'Vance Refrigeration'],
        ]);

        $response = $this->actingAs($user)->get(route('settings.logs.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Updated the company called Vance Refrigeration', escape: false);
    }

    #[Test]
    public function it_offers_to_load_more_when_a_page_is_not_enough(): void
    {
        $user = User::factory()->create();

        Log::factory()->count(11)->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Load more', escape: false);
        $response->assertSee('id="pagination"', escape: false);
    }

    #[Test]
    public function it_stops_offering_to_load_more_on_the_last_page(): void
    {
        $user = User::factory()->create();

        Log::factory()->count(3)->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.logs.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Load more', escape: false);
    }

    #[Test]
    public function it_redirects_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('settings.logs.index'));

        $response->assertRedirect(route('auth.login.new'));
    }
}
