<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company_a_model_and_a_status(): void
    {
        $asset = Asset::factory()->create();

        $this->assertTrue($asset->company()->exists());
        $this->assertTrue($asset->assetModel()->exists());
        $this->assertTrue($asset->status()->exists());
        $this->assertInstanceOf(Company::class, $asset->company);
        $this->assertInstanceOf(AssetModel::class, $asset->assetModel);
        $this->assertInstanceOf(AssetStatus::class, $asset->status);
    }

    #[Test]
    public function it_knows_the_office_it_belongs_to_and_the_one_it_is_in(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $utica = Location::factory()->create(['company_id' => $company->id]);

        $asset = Asset::factory()->create([
            'company_id' => $company->id,
            'default_location_id' => $scranton->id,
            'current_location_id' => $utica->id,
        ]);

        $this->assertEquals($scranton->id, $asset->defaultLocation->id);
        $this->assertEquals($utica->id, $asset->currentLocation->id);
    }

    #[Test]
    public function it_reads_as_deployed_while_somebody_holds_it(): void
    {
        $asset = Asset::factory()->create();

        $this->assertEquals('Ready to deploy', $asset->displayStatus);

        AssetAssignment::factory()->create(['asset_id' => $asset->id]);

        $this->assertEquals(Asset::DEPLOYED, $asset->fresh()->displayStatus);
    }

    #[Test]
    public function it_reads_as_its_own_status_again_once_the_equipment_comes_back(): void
    {
        $asset = Asset::factory()->create();
        AssetAssignment::factory()->returned()->create(['asset_id' => $asset->id]);

        $this->assertEquals('Ready to deploy', $asset->fresh()->displayStatus);
    }

    #[Test]
    public function it_reads_as_deployed_without_its_stored_status_changing(): void
    {
        $asset = Asset::factory()->create();
        $status = $asset->status_id;

        AssetAssignment::factory()->create(['asset_id' => $asset->id]);

        $this->assertEquals(Asset::DEPLOYED, $asset->fresh()->displayStatus);
        $this->assertEquals($status, $asset->fresh()->status_id);
    }

    #[Test]
    public function it_knows_whether_somebody_is_holding_it(): void
    {
        $asset = Asset::factory()->create();

        $this->assertFalse($asset->isAssigned());

        AssetAssignment::factory()->create(['asset_id' => $asset->id]);

        $this->assertTrue($asset->fresh()->isAssigned());
    }

    #[Test]
    public function it_finds_the_assignment_nobody_has_closed(): void
    {
        $asset = Asset::factory()->create();
        AssetAssignment::factory()->returned()->create(['asset_id' => $asset->id]);
        $current = AssetAssignment::factory()->create(['asset_id' => $asset->id]);

        $this->assertCount(2, $asset->assignments);
        $this->assertEquals($current->id, $asset->fresh()->activeAssignment->id);
    }

    #[Test]
    public function it_knows_whether_it_has_left_the_fleet(): void
    {
        $this->assertFalse(Asset::factory()->create()->isArchived());
        $this->assertTrue(Asset::factory()->archived()->create()->isArchived());
    }
}
