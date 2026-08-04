<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\AssetStatusTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetStatusTypeEnumTest extends TestCase
{
    #[Test]
    public function only_one_of_the_four_lets_equipment_be_handed_out(): void
    {
        $this->assertTrue(AssetStatusTypeEnum::Deployable->isDeployable());

        $this->assertFalse(AssetStatusTypeEnum::Pending->isDeployable());
        $this->assertFalse(AssetStatusTypeEnum::Undeployable->isDeployable());
        $this->assertFalse(AssetStatusTypeEnum::Archived->isDeployable());
    }

    #[Test]
    public function it_names_every_type(): void
    {
        foreach (AssetStatusTypeEnum::cases() as $type) {
            $this->assertNotEmpty($type->label());
        }
    }
}
