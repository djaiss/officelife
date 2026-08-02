<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Administration;

use App\Enums\LocationScopeEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use App\ViewModels\Settings\Administration\LocationsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_what_the_company_keeps(): void
    {
        $user = $this->company();

        Location::factory()->primary()->create(['company_id' => $user->company_id, 'country' => 'US', 'timezone' => 'America/New_York']);
        Location::factory()->create(['company_id' => $user->company_id, 'country' => 'US', 'timezone' => 'America/Chicago']);
        Location::factory()->archived()->create(['company_id' => $user->company_id, 'country' => 'FR']);

        $stats = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->stats();

        $this->assertEquals(2, $stats[0]['value']);
        $this->assertEquals('1 archived', $stats[0]['note']);
        $this->assertEquals(1, $stats[1]['value']);
        $this->assertEquals(2, $stats[2]['value']);
        $this->assertEquals(1, $stats[3]['value']);
    }

    #[Test]
    public function it_lists_the_open_offices_by_default(): void
    {
        $user = $this->company();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Location::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

        $rows = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->rows();

        $this->assertCount(1, $rows);
        $this->assertEquals('Scranton branch', $rows[0]['name']);
    }

    #[Test]
    public function it_lists_the_archived_offices_on_their_own(): void
    {
        $user = $this->company();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Location::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

        $rows = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Archived)->rows();

        $this->assertCount(1, $rows);
        $this->assertEquals('Nashua branch', $rows[0]['name']);
        $this->assertTrue($rows[0]['isArchived']);
    }

    #[Test]
    public function it_lists_every_office_when_asked_for_all_of_them(): void
    {
        $user = $this->company();

        Location::factory()->create(['company_id' => $user->company_id]);
        Location::factory()->archived()->create(['company_id' => $user->company_id]);

        $this->assertCount(2, new LocationsViewModel(user: $user, scope: LocationScopeEnum::All)->rows());
    }

    #[Test]
    public function it_keeps_the_offices_of_another_company_out(): void
    {
        $user = $this->company();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Location::factory()->create(['name' => 'Stamford branch']);

        $rows = new LocationsViewModel(user: $user, scope: LocationScopeEnum::All)->rows();

        $this->assertCount(1, $rows);
        $this->assertEquals('Scranton branch', $rows[0]['name']);
    }

    #[Test]
    public function it_searches_the_name_the_city_and_the_country(): void
    {
        $user = $this->company();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch', 'city' => 'Scranton', 'country' => 'US']);
        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Paris office', 'city' => 'Paris', 'country' => 'FR']);

        $byName = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active, search: 'scran')->rows();
        $byCity = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active, search: 'paris')->rows();
        $byCountry = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active, search: 'FR')->rows();

        $this->assertCount(1, $byName);
        $this->assertEquals('Scranton branch', $byName[0]['name']);
        $this->assertCount(1, $byCity);
        $this->assertCount(1, $byCountry);
        $this->assertEquals('Paris office', $byCountry[0]['name']);
    }

    #[Test]
    public function it_orders_the_offices_by_name_and_turns_the_order_around(): void
    {
        $user = $this->company();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

        $ascending = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->rows();
        $descending = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active, direction: 'desc')->rows();

        $this->assertEquals('Nashua branch', $ascending[0]['name']);
        $this->assertEquals('Scranton branch', $descending[0]['name']);
    }

    #[Test]
    public function it_falls_back_when_an_office_has_no_address_no_country_and_no_time_zone(): void
    {
        $user = $this->company();

        Location::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Somewhere',
            'address' => null,
            'country' => null,
            'city' => null,
            'timezone' => null,
        ]);

        $rows = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->rows();

        $this->assertEquals('no address on file', $rows[0]['address']);
        $this->assertEquals('somewhere unrecorded', $rows[0]['place']);
        $this->assertEquals('same as the company', $rows[0]['timezone']);
        $this->assertEquals('··', $rows[0]['code']);
    }

    #[Test]
    public function it_hands_the_panel_everything_it_needs_to_edit_an_office(): void
    {
        $user = $this->company();
        $location = Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);

        $drawer = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->drawer();

        $this->assertArrayHasKey($location->id, $drawer);
        $this->assertEquals('Scranton branch', $drawer[$location->id]['name']);
        $this->assertEquals(route('settings.locations.update', $location->id), $drawer[$location->id]['updateUrl']);
        $this->assertEquals(route('settings.locationArchives.create', $location->id), $drawer[$location->id]['archiveUrl']);
        $this->assertEquals(route('settings.locationArchives.destroy', $location->id), $drawer[$location->id]['restoreUrl']);
    }

    #[Test]
    public function it_says_which_of_the_three_lists_is_being_read(): void
    {
        $user = $this->company();

        $scopes = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Archived)->scopes();

        $this->assertCount(3, $scopes);
        $this->assertFalse($scopes[0]['current']);
        $this->assertTrue($scopes[1]['current']);
        $this->assertEquals(route('settings.locations.index', 'archived'), $scopes[1]['url']);
    }

    #[Test]
    public function it_knows_the_company_has_no_office_at_all(): void
    {
        $user = $this->company();

        $this->assertTrue(new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->companyHasNoOffice());

        Location::factory()->create(['company_id' => $user->company_id]);

        $this->assertFalse(new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->companyHasNoOffice());
    }

    private function company(): User
    {
        $company = Company::factory()->create();

        return User::factory()->create(['company_id' => $company->id]);
    }
}
