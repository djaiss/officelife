<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Enums\DomainEventTypeEnum;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\DomainEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckOverdueAssetReturnsTest extends TestCase
{
    use RefreshDatabase;

    private function overdueEvents(): int
    {
        return DomainEvent::query()->where('type', DomainEventTypeEnum::AssetReturnOverdue)->count();
    }

    #[Test]
    public function it_flags_equipment_that_is_late_coming_back(): void
    {
        $asset = Asset::factory()->create();
        $assignment = AssetAssignment::factory()->overdue()->create(['asset_id' => $asset->id]);

        $this->artisan('assets:check-overdue-returns')->assertSuccessful();

        $this->assertEquals(1, $this->overdueEvents());
        $this->assertNotNull($assignment->fresh()->overdue_notified_at);
        $this->assertDatabaseHas('domain_events', [
            'type' => DomainEventTypeEnum::AssetReturnOverdue->value,
            'subject_type' => Asset::class,
            'subject_id' => $asset->id,
            'actor_type' => 'system',
        ]);
    }

    #[Test]
    public function it_flags_the_same_assignment_once_however_often_it_runs(): void
    {
        AssetAssignment::factory()->overdue()->create();

        $this->artisan('assets:check-overdue-returns')->assertSuccessful();
        $this->artisan('assets:check-overdue-returns')->assertSuccessful();
        $this->artisan('assets:check-overdue-returns')->assertSuccessful();

        $this->assertEquals(1, $this->overdueEvents());
    }

    #[Test]
    public function it_says_nothing_about_equipment_that_has_come_back(): void
    {
        AssetAssignment::factory()->create([
            'expected_return_at' => now()->subWeek(),
            'returned_at' => now(),
        ]);

        $this->artisan('assets:check-overdue-returns')->assertSuccessful();

        $this->assertEquals(0, $this->overdueEvents());
    }

    #[Test]
    public function it_says_nothing_about_equipment_with_no_return_date(): void
    {
        AssetAssignment::factory()->create(['expected_return_at' => null]);

        $this->artisan('assets:check-overdue-returns')->assertSuccessful();

        $this->assertEquals(0, $this->overdueEvents());
    }

    #[Test]
    public function it_says_nothing_about_equipment_that_is_not_due_back_yet(): void
    {
        AssetAssignment::factory()->create(['expected_return_at' => now()->addWeek()]);

        $this->artisan('assets:check-overdue-returns')->assertSuccessful();

        $this->assertEquals(0, $this->overdueEvents());
    }

    #[Test]
    public function it_flags_each_late_assignment_separately(): void
    {
        AssetAssignment::factory()->overdue()->count(3)->create();

        $this->artisan('assets:check-overdue-returns')->assertSuccessful();

        $this->assertEquals(3, $this->overdueEvents());
    }
}
