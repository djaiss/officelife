<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\AssetModel;
use App\Models\Company;
use App\Models\Manufacturer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManufacturerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $manufacturer = Manufacturer::factory()->create();

        $this->assertTrue($manufacturer->company()->exists());
        $this->assertInstanceOf(Company::class, $manufacturer->company);
    }

    #[Test]
    public function it_lists_the_models_it_makes(): void
    {
        $manufacturer = Manufacturer::factory()->create();
        AssetModel::factory()->count(2)->create([
            'company_id' => $manufacturer->company_id,
            'manufacturer_id' => $manufacturer->id,
        ]);

        $this->assertCount(2, $manufacturer->assetModels);
    }

    #[Test]
    public function it_keeps_the_support_details_apart_from_the_website(): void
    {
        $manufacturer = Manufacturer::factory()->create([
            'website_url' => 'https://apple.com',
            'support_url' => 'https://support.apple.com',
            'support_email' => 'support@apple.com',
        ]);

        $this->assertEquals('https://apple.com', $manufacturer->website_url);
        $this->assertEquals('https://support.apple.com', $manufacturer->support_url);
        $this->assertEquals('support@apple.com', $manufacturer->support_email);
    }
}
