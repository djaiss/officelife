<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CheckinAsset;
use App\Enums\AssetConditionEnum;
use App\Enums\ModuleEnum;
use App\Enums\OccurrenceTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckinAssetTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->withModule(ModuleEnum::Assets)->create();
        $this->author = $this->grant(
            User::factory()->create(['company_id' => $this->company->id]),
            PermissionEnum::AssetCheckout,
        );
    }

    #[Test]
    public function it_takes_the_equipment_back(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $assignment = AssetAssignment::factory()->to($employee)->create(['asset_id' => $asset->id]);
        $scranton = Location::factory()->create(['company_id' => $this->company->id]);

        $closed = new CheckinAsset(
            author: $this->author,
            asset: $asset,
            condition: AssetConditionEnum::Fair,
            notes: 'Scratched lid',
            location: $scranton,
        )->execute();

        $this->assertEquals($assignment->id, $closed->id);
        $this->assertNotNull($closed->returned_at);
        $this->assertEquals(AssetConditionEnum::Fair, $closed->condition_at_checkin);
        $this->assertEquals('Scratched lid', $closed->checkin_notes);
        $this->assertEquals($scranton->id, $closed->returned_to_location_id);
        $this->assertEquals($scranton->id, $asset->fresh()->current_location_id);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AssetCheckin,
        );
    }

    #[Test]
    public function it_closes_the_assignment_rather_than_deleting_it(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        $assignment = AssetAssignment::factory()->create(['asset_id' => $asset->id]);

        new CheckinAsset(author: $this->author, asset: $asset)->execute();

        $this->assertDatabaseHas('asset_assignments', ['id' => $assignment->id]);
        $this->assertFalse($asset->fresh()->isAssigned());
        $this->assertCount(1, $asset->fresh()->assignments);
    }

    #[Test]
    public function it_leaves_the_status_alone_when_none_is_given(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        AssetAssignment::factory()->create(['asset_id' => $asset->id]);
        $status = $asset->status_id;

        new CheckinAsset(author: $this->author, asset: $asset)->execute();

        $this->assertEquals($status, $asset->fresh()->status_id);
        $this->assertEquals('Ready to deploy', $asset->fresh()->displayStatus);
    }

    #[Test]
    public function it_writes_the_status_it_was_told_to(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        AssetAssignment::factory()->create(['asset_id' => $asset->id]);
        $repair = AssetStatus::query()->where('key', AssetStatus::AWAITING_REPAIR)->firstOrFail();

        new CheckinAsset(
            author: $this->author,
            asset: $asset,
            condition: AssetConditionEnum::Damaged,
            status: $repair,
        )->execute();

        $this->assertEquals($repair->id, $asset->fresh()->status_id);
        $this->assertEquals('Awaiting repair', $asset->fresh()->displayStatus);
    }

    #[Test]
    public function it_refuses_equipment_nobody_has(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);

        new CheckinAsset(author: $this->author, asset: $asset)->execute();
    }

    #[Test]
    public function it_refuses_an_office_of_another_company(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        AssetAssignment::factory()->create(['asset_id' => $asset->id]);

        new CheckinAsset(
            author: $this->author,
            asset: $asset,
            location: Location::factory()->create(),
        )->execute();
    }

    #[Test]
    public function it_publishes_that_the_equipment_came_back(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        AssetAssignment::factory()->create(['asset_id' => $asset->id]);

        new CheckinAsset(author: $this->author, asset: $asset)->execute();

        $this->assertDatabaseHas('occurrences', [
            'company_id' => $this->company->id,
            'type' => OccurrenceTypeEnum::AssetCheckedIn->value,
            'subject_type' => Asset::class,
            'subject_id' => $asset->id,
        ]);
    }

    #[Test]
    public function it_throws_when_the_author_may_not_take_equipment_back(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        AssetAssignment::factory()->create(['asset_id' => $asset->id]);

        new CheckinAsset(
            author: User::factory()->create(['company_id' => $this->company->id]),
            asset: $asset,
        )->execute();
    }
}
