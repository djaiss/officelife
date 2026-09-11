<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfficeArchiveControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_archives_an_office(): void
    {
        Queue::fake();

        $user = $this->administrator();
        $office = Office::factory()->create(['company_id' => $user->company_id]);

        $response = $this->actingAs($user)
            ->from(route('settings.offices.index'))
            ->post(route('settings.officeArchives.create', $office->id));

        $response->assertRedirect(route('settings.offices.index'));
        $response->assertSessionHas('status', 'The office is archived.');

        $this->assertTrue($office->refresh()->isArchived());
    }

    #[Test]
    public function it_reopens_an_office(): void
    {
        Queue::fake();

        $user = $this->administrator();
        $office = Office::factory()->archived()->create(['company_id' => $user->company_id]);

        $response = $this->actingAs($user)
            ->from(route('settings.offices.index', 'archived'))
            ->delete(route('settings.officeArchives.destroy', $office->id));

        $response->assertRedirect(route('settings.offices.index', 'archived'));
        $response->assertSessionHas('status', 'The office is open again.');

        $this->assertFalse($office->refresh()->isArchived());
    }

    #[Test]
    public function it_cannot_archive_an_office_of_another_company(): void
    {
        $user = $this->administrator();
        $office = Office::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.officeArchives.create', $office->id))
            ->assertNotFound();
    }

    #[Test]
    public function it_stops_somebody_who_may_not_administer_the_company_from_archiving_an_office(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeMember(User::factory()->create(['company_id' => $company->id]));
        $office = Office::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)
            ->post(route('settings.officeArchives.create', $office->id))
            ->assertNotFound();

        $this->assertFalse($office->refresh()->isArchived());
    }

    #[Test]
    public function it_goes_back_to_the_list_it_was_asked_from(): void
    {
        Queue::fake();

        $user = $this->administrator();
        $office = Office::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->from(route('settings.offices.index', 'all'))
            ->post(route('settings.officeArchives.create', $office->id))
            ->assertRedirect(route('settings.offices.index', 'all'));
    }

    private function administrator(): User
    {
        $company = Company::factory()->create();

        return $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
    }
}
