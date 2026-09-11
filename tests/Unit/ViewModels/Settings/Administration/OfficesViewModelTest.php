<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Administration;

use App\Enums\OfficeScopeEnum;
use App\Models\Company;
use App\Models\Office;
use App\Models\User;
use App\ViewModels\Settings\Administration\OfficesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfficesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_what_the_company_keeps(): void
    {
        $user = $this->company();

        Office::factory()->primary()->create(['company_id' => $user->company_id, 'country' => 'US', 'timezone' => 'America/New_York']);
        Office::factory()->create(['company_id' => $user->company_id, 'country' => 'US', 'timezone' => 'America/Chicago']);
        Office::factory()->archived()->create(['company_id' => $user->company_id, 'country' => 'FR', 'timezone' => 'Europe/Paris']);

        $stats = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Active)->stats();

        $this->assertCount(3, $stats);
        $this->assertEquals(2, $stats[0]['value']);
        $this->assertEquals(1, $stats[1]['value']);
        $this->assertEquals(2, $stats[2]['value']);
    }

    #[Test]
    public function it_lists_the_open_offices_by_default(): void
    {
        $user = $this->company();

        Office::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Office::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

        $rows = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Active)->rows();

        $this->assertCount(1, $rows);
        $this->assertEquals('Scranton branch', $rows[0]['name']);
    }

    #[Test]
    public function it_lists_the_archived_offices_on_their_own(): void
    {
        $user = $this->company();

        Office::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Office::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

        $rows = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Archived)->rows();

        $this->assertCount(1, $rows);
        $this->assertEquals('Nashua branch', $rows[0]['name']);
        $this->assertTrue($rows[0]['isArchived']);
    }

    #[Test]
    public function it_lists_every_office_when_asked_for_all_of_them(): void
    {
        $user = $this->company();

        Office::factory()->create(['company_id' => $user->company_id]);
        Office::factory()->archived()->create(['company_id' => $user->company_id]);

        $this->assertCount(2, new OfficesViewModel(user: $user, scope: OfficeScopeEnum::All)->rows());
    }

    #[Test]
    public function it_badges_the_head_office_and_the_archived_ones(): void
    {
        $user = $this->company();

        Office::factory()->primary()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Office::factory()->create(['company_id' => $user->company_id, 'name' => 'Utica branch']);
        Office::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Stamford branch']);

        $rows = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::All)->rows();

        $this->assertEquals('Head office', $rows[0]['badge']);
        $this->assertEquals('Archived', $rows[1]['badge']);
        $this->assertEquals('', $rows[2]['badge']);
    }

    #[Test]
    public function it_keeps_the_offices_of_another_company_out(): void
    {
        $user = $this->company();

        Office::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Office::factory()->create(['name' => 'Stamford branch']);

        $rows = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::All)->rows();

        $this->assertCount(1, $rows);
        $this->assertEquals('Scranton branch', $rows[0]['name']);
    }

    #[Test]
    public function it_searches_the_name_the_city_and_the_country(): void
    {
        $user = $this->company();

        Office::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch', 'city' => 'Scranton', 'country' => 'US']);
        Office::factory()->create(['company_id' => $user->company_id, 'name' => 'Paris office', 'city' => 'Paris', 'country' => 'FR']);

        $byName = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Active, search: 'scran')->rows();
        $byCity = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Active, search: 'paris')->rows();
        $byCountry = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Active, search: 'FR')->rows();

        $this->assertCount(1, $byName);
        $this->assertEquals('Scranton branch', $byName[0]['name']);
        $this->assertCount(1, $byCity);
        $this->assertCount(1, $byCountry);
        $this->assertEquals('Paris office', $byCountry[0]['name']);
    }

    #[Test]
    public function it_orders_the_offices_by_office_or_by_city(): void
    {
        $user = $this->company();

        Office::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch', 'city' => 'Akron', 'country' => 'US']);
        Office::factory()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch', 'city' => 'Utica', 'country' => 'US']);

        $byName = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Active)->rows();
        $byPlace = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Active, sort: 'place')->rows();

        $this->assertEquals('Nashua branch', $byName[0]['name']);
        $this->assertEquals('Scranton branch', $byPlace[0]['name']);
    }

    #[Test]
    public function it_says_which_order_the_list_is_in_and_leads_to_the_other_one(): void
    {
        $user = $this->company();

        $byName = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Active)->sortToggle();
        $byPlace = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Active, sort: 'place')->sortToggle();

        $this->assertEquals('Sorted by office', $byName['label']);
        $this->assertEquals(route('settings.offices.index', ['sort' => 'place']), $byName['url']);
        $this->assertEquals('Sorted by city', $byPlace['label']);
        $this->assertEquals(route('settings.offices.index'), $byPlace['url']);
    }

    #[Test]
    public function it_falls_back_when_an_office_has_no_country_and_no_time_zone(): void
    {
        $user = $this->company();

        Office::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Somewhere',
            'address' => null,
            'country' => null,
            'city' => null,
            'timezone' => null,
        ]);

        $rows = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Active)->rows();

        $this->assertEquals('somewhere unrecorded', $rows[0]['place']);
        $this->assertEquals('same as the company', $rows[0]['timezone']);
    }

    #[Test]
    public function it_hands_the_panel_everything_it_needs_to_edit_an_office(): void
    {
        $user = $this->company();
        $office = Office::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);

        $drawer = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Active)->drawer();

        $this->assertArrayHasKey($office->id, $drawer);
        $this->assertEquals('Scranton branch', $drawer[$office->id]['name']);
        $this->assertEquals(route('settings.offices.update', $office->id), $drawer[$office->id]['updateUrl']);
        $this->assertEquals(route('settings.officeArchives.create', $office->id), $drawer[$office->id]['archiveUrl']);
        $this->assertEquals(route('settings.officeArchives.destroy', $office->id), $drawer[$office->id]['restoreUrl']);
    }

    #[Test]
    public function it_says_which_of_the_three_lists_is_being_read(): void
    {
        $user = $this->company();

        $scopes = new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Archived)->scopes();

        $this->assertCount(3, $scopes);
        $this->assertFalse($scopes[0]['current']);
        $this->assertTrue($scopes[1]['current']);
        $this->assertEquals(route('settings.offices.index', 'archived'), $scopes[1]['url']);
    }

    #[Test]
    public function it_knows_the_company_has_no_office_at_all(): void
    {
        $user = $this->company();

        $this->assertTrue(new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Active)->companyHasNoOffice());

        Office::factory()->create(['company_id' => $user->company_id]);

        $this->assertFalse(new OfficesViewModel(user: $user, scope: OfficeScopeEnum::Active)->companyHasNoOffice());
    }

    private function company(): User
    {
        $company = Company::factory()->create();

        return User::factory()->create(['company_id' => $company->id]);
    }
}
