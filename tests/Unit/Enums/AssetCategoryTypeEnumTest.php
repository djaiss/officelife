<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\AssetCategoryTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetCategoryTypeEnumTest extends TestCase
{
    #[Test]
    public function only_serialised_equipment_can_be_recorded_yet(): void
    {
        $this->assertTrue(AssetCategoryTypeEnum::Asset->isAvailable());

        $this->assertFalse(AssetCategoryTypeEnum::Accessory->isAvailable());
        $this->assertFalse(AssetCategoryTypeEnum::Consumable->isAvailable());
        $this->assertFalse(AssetCategoryTypeEnum::Component->isAvailable());
        $this->assertFalse(AssetCategoryTypeEnum::Licence->isAvailable());
    }

    #[Test]
    public function it_names_every_family(): void
    {
        foreach (AssetCategoryTypeEnum::cases() as $type) {
            $this->assertNotEmpty($type->label());
        }
    }
}
