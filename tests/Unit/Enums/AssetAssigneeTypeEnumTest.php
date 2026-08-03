<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\AssetAssigneeTypeEnum;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetAssigneeTypeEnumTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_knows_which_model_each_type_stands_for(): void
    {
        $this->assertEquals(Employee::class, AssetAssigneeTypeEnum::Employee->model());
        $this->assertEquals(Location::class, AssetAssigneeTypeEnum::Location->model());
        $this->assertEquals(Asset::class, AssetAssigneeTypeEnum::Asset->model());
    }

    #[Test]
    public function it_finds_the_type_standing_for_a_model(): void
    {
        $this->assertEquals(AssetAssigneeTypeEnum::Employee, AssetAssigneeTypeEnum::forModel(Employee::factory()->make()));
        $this->assertEquals(AssetAssigneeTypeEnum::Location, AssetAssigneeTypeEnum::forModel(Location::factory()->make()));
        $this->assertEquals(AssetAssigneeTypeEnum::Asset, AssetAssigneeTypeEnum::forModel(Asset::factory()->make()));
    }

    #[Test]
    public function it_finds_nothing_for_a_model_equipment_cannot_be_handed_to(): void
    {
        $this->assertNull(AssetAssigneeTypeEnum::forModel(Company::factory()->make()));
    }
}
