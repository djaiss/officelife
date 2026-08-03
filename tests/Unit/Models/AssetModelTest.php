<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Company;
use App\Models\Manufacturer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company_a_manufacturer_and_a_category(): void
    {
        $assetModel = AssetModel::factory()->create();

        $this->assertTrue($assetModel->company()->exists());
        $this->assertTrue($assetModel->manufacturer()->exists());
        $this->assertTrue($assetModel->assetCategory()->exists());
        $this->assertInstanceOf(Company::class, $assetModel->company);
        $this->assertInstanceOf(Manufacturer::class, $assetModel->manufacturer);
        $this->assertInstanceOf(AssetCategory::class, $assetModel->assetCategory);
    }

    #[Test]
    public function it_lists_every_piece_of_equipment_of_that_model(): void
    {
        $assetModel = AssetModel::factory()->create();
        Asset::factory()->count(3)->create([
            'company_id' => $assetModel->company_id,
            'asset_model_id' => $assetModel->id,
        ]);

        $this->assertCount(3, $assetModel->assets);
    }

    #[Test]
    public function it_carries_what_every_piece_of_that_model_has_in_common(): void
    {
        $assetModel = AssetModel::factory()->create([
            'name' => 'Apple MacBook Pro 14-inch M4 Pro',
            'useful_life_months' => 48,
        ]);

        $this->assertEquals('Apple MacBook Pro 14-inch M4 Pro', $assetModel->name);
        $this->assertEquals(48, $assetModel->useful_life_months);
        $this->assertFalse($assetModel->is_requestable);
    }
}
