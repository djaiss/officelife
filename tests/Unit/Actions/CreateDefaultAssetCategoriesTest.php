<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateDefaultAssetCategories;
use App\Enums\AssetCategoryTypeEnum;
use App\Models\AssetCategory;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateDefaultAssetCategoriesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_a_company_a_catalogue_to_start_from(): void
    {
        $company = Company::factory()->create();

        $categories = new CreateDefaultAssetCategories(company: $company)->execute();

        $this->assertCount(7, $categories);
        $this->assertEquals(
            [
                'Laptops',
                'Desktops',
                'Monitors',
                'Phones',
                'Tablets',
                'Docking stations',
                'Security badges',
            ],
            $categories->pluck('name')->all(),
        );

        foreach ($categories as $category) {
            $this->assertEquals($company->id, $category->company_id);
            $this->assertEquals(AssetCategoryTypeEnum::Asset, $category->type);
            $this->assertFalse($category->requires_acceptance);
            $this->assertNull($category->eula_text);
        }
    }

    #[Test]
    public function it_offers_serialised_equipment_only(): void
    {
        $categories = new CreateDefaultAssetCategories(company: Company::factory()->create())->execute();

        foreach ($categories as $category) {
            $this->assertTrue($category->type->isAvailable());
        }
    }

    #[Test]
    public function it_leaves_a_company_that_already_has_a_catalogue_alone(): void
    {
        $company = Company::factory()->create();
        AssetCategory::factory()->create(['company_id' => $company->id, 'name' => 'Forklifts']);

        $categories = new CreateDefaultAssetCategories(company: $company)->execute();

        $this->assertCount(0, $categories);
        $this->assertEquals(['Forklifts'], $company->assetCategories()->pluck('name')->all());
    }

    #[Test]
    public function it_gives_the_categories_to_that_company_alone(): void
    {
        $dunderMifflin = Company::factory()->create();
        $other = Company::factory()->create();

        new CreateDefaultAssetCategories(company: $dunderMifflin)->execute();

        $this->assertCount(7, $dunderMifflin->assetCategories()->get());
        $this->assertCount(0, $other->assetCategories()->get());
    }
}
