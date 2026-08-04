<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CheckoutAsset;
use App\Enums\AssetAssigneeTypeEnum;
use App\Enums\AssetConditionEnum;
use App\Enums\ModuleEnum;
use App\Enums\OccurrenceTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Asset;
use App\Models\AssetAssignment;
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

class CheckoutAssetTest extends TestCase
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
    public function it_hands_equipment_to_a_colleague(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        $assignment = new CheckoutAsset(
            author: $this->author,
            asset: $asset,
            assignee: $employee,
            expectedReturnAt: now()->addMonth(),
            condition: AssetConditionEnum::Good,
            notes: 'Charger in the box',
        )->execute();

        $this->assertInstanceOf(AssetAssignment::class, $assignment);
        $this->assertDatabaseHas('asset_assignments', [
            'id' => $assignment->id,
            'asset_id' => $asset->id,
            'assignee_type' => 'employee',
            'assignee_id' => $employee->id,
            'assigned_by_user_id' => $this->author->id,
            'returned_at' => null,
            'condition_at_checkout' => 'good',
            'checkout_notes' => 'Charger in the box',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AssetCheckout,
        );
    }

    #[Test]
    public function it_moves_the_equipment_to_the_office_it_was_sent_to(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        $scranton = Location::factory()->create(['company_id' => $this->company->id]);

        new CheckoutAsset(
            author: $this->author,
            asset: $asset,
            assignee: Employee::factory()->create(['company_id' => $this->company->id]),
            location: $scranton,
        )->execute();

        $this->assertEquals($scranton->id, $asset->fresh()->current_location_id);
    }

    #[Test]
    public function it_leaves_the_equipment_where_it_was_when_no_office_is_given(): void
    {
        Queue::fake();

        $utica = Location::factory()->create(['company_id' => $this->company->id]);
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'current_location_id' => $utica->id,
        ]);

        new CheckoutAsset(
            author: $this->author,
            asset: $asset,
            assignee: Employee::factory()->create(['company_id' => $this->company->id]),
        )->execute();

        $this->assertEquals($utica->id, $asset->fresh()->current_location_id);
    }

    #[Test]
    public function it_never_writes_the_status(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        $status = $asset->status_id;

        new CheckoutAsset(
            author: $this->author,
            asset: $asset,
            assignee: Employee::factory()->create(['company_id' => $this->company->id]),
        )->execute();

        $this->assertEquals($status, $asset->fresh()->status_id);
        $this->assertEquals(Asset::DEPLOYED, $asset->fresh()->displayStatus);
    }

    #[Test]
    public function it_refuses_equipment_somebody_already_has(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        AssetAssignment::factory()->create(['asset_id' => $asset->id]);

        new CheckoutAsset(
            author: $this->author,
            asset: $asset,
            assignee: Employee::factory()->create(['company_id' => $this->company->id]),
        )->execute();
    }

    #[Test]
    public function it_refuses_equipment_that_is_not_ready_to_be_handed_out(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        $asset = Asset::factory()->undeployable()->create(['company_id' => $this->company->id]);

        new CheckoutAsset(
            author: $this->author,
            asset: $asset,
            assignee: Employee::factory()->create(['company_id' => $this->company->id]),
        )->execute();
    }

    #[Test]
    public function it_refuses_equipment_that_has_left_the_fleet(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        $asset = Asset::factory()->archived()->create(['company_id' => $this->company->id]);

        new CheckoutAsset(
            author: $this->author,
            asset: $asset,
            assignee: Employee::factory()->create(['company_id' => $this->company->id]),
        )->execute();
    }

    #[Test]
    public function it_hands_equipment_to_an_office(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        $room = Location::factory()->create(['company_id' => $this->company->id]);

        $assignment = new CheckoutAsset(author: $this->author, asset: $asset, assignee: $room)->execute();

        $this->assertEquals(AssetAssigneeTypeEnum::Location, $assignment->assignee_type);
        $this->assertEquals($room->id, $assignment->assignee_id);
    }

    #[Test]
    public function it_hands_equipment_to_other_equipment(): void
    {
        Queue::fake();

        $dock = Asset::factory()->create(['company_id' => $this->company->id]);
        $laptop = Asset::factory()->create(['company_id' => $this->company->id]);

        $assignment = new CheckoutAsset(author: $this->author, asset: $dock, assignee: $laptop)->execute();

        $this->assertEquals(AssetAssigneeTypeEnum::Asset, $assignment->assignee_type);
        $this->assertEquals($laptop->id, $assignment->assignee_id);
    }

    #[Test]
    public function it_refuses_to_leave_equipment_holding_itself(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);

        new CheckoutAsset(author: $this->author, asset: $asset, assignee: $asset)->execute();
    }

    #[Test]
    public function it_refuses_a_loop_of_equipment_several_deep(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        $first = Asset::factory()->create(['company_id' => $this->company->id]);
        $second = Asset::factory()->create(['company_id' => $this->company->id]);
        $third = Asset::factory()->create(['company_id' => $this->company->id]);

        new CheckoutAsset(author: $this->author, asset: $second, assignee: $first)->execute();
        new CheckoutAsset(author: $this->author, asset: $third, assignee: $second)->execute();

        new CheckoutAsset(author: $this->author, asset: $first, assignee: $third)->execute();
    }

    #[Test]
    public function it_refuses_a_colleague_of_another_company(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);

        new CheckoutAsset(
            author: $this->author,
            asset: $asset,
            assignee: Employee::factory()->create(),
        )->execute();
    }

    #[Test]
    public function it_publishes_that_the_equipment_was_handed_over(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        new CheckoutAsset(author: $this->author, asset: $asset, assignee: $employee)->execute();

        $this->assertDatabaseHas('occurrences', [
            'company_id' => $this->company->id,
            'type' => OccurrenceTypeEnum::AssetCheckedOut->value,
            'subject_type' => Asset::class,
            'subject_id' => $asset->id,
            'actor_id' => $this->author->id,
        ]);
    }

    #[Test]
    public function it_throws_when_the_author_may_not_hand_equipment_out(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        $author = User::factory()->create(['company_id' => $this->company->id]);

        new CheckoutAsset(
            author: $author,
            asset: $asset,
            assignee: Employee::factory()->create(['company_id' => $this->company->id]),
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_company_has_not_turned_the_module_on(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = $this->grant(
            User::factory()->create(['company_id' => $company->id]),
            PermissionEnum::AssetCheckout,
        );
        $asset = Asset::factory()->create(['company_id' => $company->id]);

        new CheckoutAsset(
            author: $author,
            asset: $asset,
            assignee: Employee::factory()->create(['company_id' => $company->id]),
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_equipment_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CheckoutAsset(
            author: $this->author,
            asset: Asset::factory()->create(),
            assignee: Employee::factory()->create(['company_id' => $this->company->id]),
        )->execute();
    }
}
