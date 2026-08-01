<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Profile;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmergencyContactControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_emergency_contact(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->makeMember($user);

        $response = $this->actingAs($user)->put(route('settings.emergencyContact.update'), [
            'name' => 'Mose Schrute',
            'phone' => '+1 570 555 0182',
            'relationship' => 'Cousin',
        ]);

        $response->assertRedirect(route('settings.profile.index'));
        $response->assertSessionHas('status', 'Your emergency contact is saved.');
        $response->assertSessionHas('status_description', 'Only you and your company administrators can see this.');

        $employee->refresh();

        $this->assertEquals('Mose Schrute', $employee->emergency_contact_name);
        $this->assertEquals('+1 570 555 0182', $employee->emergency_contact_phone);
        $this->assertEquals('Cousin', $employee->emergency_contact_relationship);
    }

    #[Test]
    public function it_shows_the_box_to_somebody_who_may_read_the_details(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'emergency_contact_name' => 'Roy Anderson',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->makeMember($user);

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertOk();
        $response->assertSee('Emergency contact');
        $response->assertSee('Roy Anderson');
    }

    /**
     * The form is left out rather than shown empty, since an empty form that
     * saves is a form that quietly wipes what somebody may not read.
     */
    #[Test]
    public function it_leaves_the_box_out_for_somebody_who_may_not_read_the_details(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'emergency_contact_name' => 'Roy Anderson',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->grant($user, PermissionEnum::EmployeeUpdatePrivate, ScopeEnum::Self);

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertOk();
        $response->assertDontSee('Roy Anderson');
        $response->assertDontSee('Who we should call if something happens to you at work.');
    }

    #[Test]
    public function it_redirects_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->put(route('settings.emergencyContact.update'), [
            'name' => 'Mose Schrute',
        ]);

        $response->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_refuses_a_name_that_is_too_long(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->makeMember($user);

        $response = $this->actingAs($user)->put(route('settings.emergencyContact.update'), [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertSessionHasErrors('name');
    }
}
