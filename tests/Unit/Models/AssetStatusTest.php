<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AssetStatusTypeEnum;
use App\Models\Asset;
use App\Models\AssetStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetStatusTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_every_company_the_same_five_statuses(): void
    {
        $system = AssetStatus::query()->whereNull('company_id')->get();

        $this->assertCount(5, $system);
        $this->assertEquals(
            [
                AssetStatus::AWAITING_REPAIR,
                AssetStatus::LOST,
                AssetStatus::PENDING,
                AssetStatus::READY_TO_DEPLOY,
                AssetStatus::RETIRED,
            ],
            $system->pluck('key')->sort()->values()->all(),
        );

        foreach ($system as $status) {
            $this->assertTrue($status->is_system);
        }
    }

    #[Test]
    public function it_ships_no_deployed_status_because_holding_is_not_a_state(): void
    {
        $this->assertFalse(
            AssetStatus::query()->whereNull('company_id')->where('name', Asset::DEPLOYED)->exists(),
        );
    }

    #[Test]
    public function it_belongs_to_a_company_only_when_a_company_added_it(): void
    {
        $this->assertNull(AssetStatus::query()->where('key', AssetStatus::LOST)->first()->company);
        $this->assertNotNull(AssetStatus::factory()->create()->company);
    }

    #[Test]
    public function it_knows_whether_equipment_in_it_may_be_handed_out(): void
    {
        $this->assertTrue(AssetStatus::query()->where('key', AssetStatus::READY_TO_DEPLOY)->first()->isDeployable());
        $this->assertFalse(AssetStatus::query()->where('key', AssetStatus::AWAITING_REPAIR)->first()->isDeployable());
        $this->assertFalse(AssetStatus::query()->where('key', AssetStatus::PENDING)->first()->isDeployable());
        $this->assertFalse(AssetStatus::query()->where('key', AssetStatus::RETIRED)->first()->isDeployable());
    }

    #[Test]
    public function it_knows_which_status_means_the_equipment_has_gone_missing(): void
    {
        $this->assertTrue(AssetStatus::query()->where('key', AssetStatus::LOST)->first()->meansLost());
        $this->assertFalse(AssetStatus::query()->where('key', AssetStatus::AWAITING_REPAIR)->first()->meansLost());
        $this->assertFalse(AssetStatus::factory()->undeployable()->create()->meansLost());
    }

    #[Test]
    public function it_lists_the_equipment_currently_in_it(): void
    {
        $status = AssetStatus::query()->where('key', AssetStatus::READY_TO_DEPLOY)->first();
        Asset::factory()->create(['status_id' => $status->id]);

        $this->assertTrue($status->assets()->exists());
    }

    #[Test]
    public function a_company_status_declares_one_of_the_four_types(): void
    {
        $status = AssetStatus::factory()->create([
            'name' => 'Awaiting wipe',
            'type' => AssetStatusTypeEnum::Undeployable,
        ]);

        $this->assertEquals(AssetStatusTypeEnum::Undeployable, $status->type);
        $this->assertNull($status->key);
        $this->assertFalse($status->is_system);
        $this->assertFalse($status->isDeployable());
    }
}
