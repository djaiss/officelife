<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AssetAssigneeTypeEnum;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetAssignmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_piece_of_equipment(): void
    {
        $assignment = AssetAssignment::factory()->create();

        $this->assertTrue($assignment->asset()->exists());
        $this->assertInstanceOf(Asset::class, $assignment->asset);
    }

    #[Test]
    public function it_can_be_held_by_a_colleague_an_office_or_other_equipment(): void
    {
        $employee = Employee::factory()->create();
        $location = Location::factory()->create();
        $dock = Asset::factory()->create();

        $this->assertInstanceOf(Employee::class, AssetAssignment::factory()->to($employee)->create()->assignee);
        $this->assertInstanceOf(Location::class, AssetAssignment::factory()->to($location)->create()->assignee);
        $this->assertInstanceOf(Asset::class, AssetAssignment::factory()->to($dock)->create()->assignee);
    }

    #[Test]
    public function it_stores_what_is_holding_it_as_a_short_name(): void
    {
        $assignment = AssetAssignment::factory()->to(Location::factory()->create())->create();

        $this->assertEquals(AssetAssigneeTypeEnum::Location, $assignment->assignee_type);
        $this->assertDatabaseHas('asset_assignments', [
            'id' => $assignment->id,
            'assignee_type' => 'location',
        ]);
    }

    #[Test]
    public function it_knows_whether_somebody_still_has_the_equipment(): void
    {
        $this->assertTrue(AssetAssignment::factory()->create()->isActive());
        $this->assertFalse(AssetAssignment::factory()->returned()->create()->isActive());
    }

    #[Test]
    public function it_knows_whether_the_equipment_is_late_coming_back(): void
    {
        $this->assertTrue(AssetAssignment::factory()->overdue()->create()->isOverdue());
        $this->assertFalse(AssetAssignment::factory()->create()->isOverdue());
        $this->assertFalse(AssetAssignment::factory()->create([
            'expected_return_at' => now()->addWeek(),
        ])->isOverdue());
    }

    #[Test]
    public function it_is_not_late_once_the_equipment_has_come_back(): void
    {
        $assignment = AssetAssignment::factory()->create([
            'expected_return_at' => now()->subWeek(),
            'returned_at' => now(),
        ]);

        $this->assertFalse($assignment->isOverdue());
    }
}
