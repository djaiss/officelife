<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings;

use App\Enums\UserActionEnum;
use App\Models\Company;
use App\Models\EmailSent;
use App\Models\Employee;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_profile_screen(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee('Pam', escape: false);
        $response->assertSee('Beesly', escape: false);
        $response->assertSee('Dunder Mifflin', escape: false);
    }

    #[Test]
    public function it_shows_the_toast_of_the_previous_save(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'status' => 'Your details are saved.',
                'status_description' => 'Your colleagues see them right away.',
            ])
            ->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee('id="notifications"', escape: false);
        $response->assertSee('Your details are saved.', escape: false);
        $response->assertSee('Your colleagues see them right away.', escape: false);
    }

    #[Test]
    public function it_shows_the_latest_logs_and_offers_to_browse_the_rest(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        Log::factory()->count(6)->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'action' => UserActionEnum::CompanyUpdate->value,
            'parameters' => ['name' => 'Dunder Mifflin'],
        ]);

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee('Updated the company called Dunder Mifflin', escape: false);
        $response->assertSee('Browse all activity', escape: false);
        $response->assertSee(route('settings.logs.index'), escape: false);
    }

    #[Test]
    public function it_hides_the_link_to_the_logs_when_there_is_nothing_more_to_read(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        Log::factory()->count(2)->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Browse all activity', escape: false);
    }

    #[Test]
    public function it_shows_the_latest_emails_sent_and_offers_to_browse_the_rest(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->count(7)->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'sent_at' => now()->subWeek(),
        ]);
        EmailSent::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'email_address' => 'creed.bratton@dundermifflin.com',
            'subject' => 'Confirm your email address',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee('creed.bratton@dundermifflin.com', escape: false);
        $response->assertSee('Confirm your email address', escape: false);
        $response->assertSee('Browse all emails', escape: false);
        $response->assertSee(route('settings.emailsSent.index'), escape: false);
    }

    #[Test]
    public function it_hides_the_link_to_the_emails_when_there_is_nothing_more_to_read(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->count(6)->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Browse all emails', escape: false);
    }

    #[Test]
    public function it_redirects_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('settings.profile.index'));

        $response->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_updates_the_details(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'display_name' => 'The Boss',
            'work_email' => 'michael.scott@dundermifflin.com',
        ]);

        $response->assertRedirect(route('settings.profile.index'));
        $response->assertSessionHas('status', 'Your details are saved.');
        $response->assertSessionHas('status_description', 'Your colleagues see them right away.');

        $employee->refresh();

        $this->assertEquals('Michael', $employee->first_name);
        $this->assertEquals('Scott', $employee->last_name);
        $this->assertEquals('The Boss', $employee->display_name);
        $this->assertEquals('michael.scott@dundermifflin.com', $employee->work_email);
    }

    #[Test]
    public function it_refuses_details_without_a_name(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'first_name' => '',
            'last_name' => '',
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
    }

    #[Test]
    public function it_refuses_a_work_email_that_is_not_an_email(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'first_name' => 'Angela',
            'last_name' => 'Martin',
            'work_email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('work_email');
    }
}
