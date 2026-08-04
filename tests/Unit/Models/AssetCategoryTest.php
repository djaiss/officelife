<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AssetCategoryTypeEnum;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $category = AssetCategory::factory()->create();

        $this->assertTrue($category->company()->exists());
        $this->assertInstanceOf(Company::class, $category->company);
    }

    #[Test]
    public function it_lists_the_models_filed_under_it(): void
    {
        $category = AssetCategory::factory()->create();
        AssetModel::factory()->count(2)->create([
            'company_id' => $category->company_id,
            'asset_category_id' => $category->id,
        ]);

        $this->assertCount(2, $category->assetModels);
    }

    #[Test]
    public function it_carries_the_rules_that_apply_to_the_whole_family(): void
    {
        $category = AssetCategory::factory()->requiringAcceptance()->create();

        $this->assertTrue($category->requires_acceptance);
        $this->assertNotEmpty($category->eula_text);
        $this->assertEquals(AssetCategoryTypeEnum::Asset, $category->type);
    }
}
