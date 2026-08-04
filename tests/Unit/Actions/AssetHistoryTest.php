<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CheckinAsset;
use App\Actions\CheckoutAsset;
use App\Enums\AssetConditionEnum;
use App\Enums\ModuleEnum;
use App\Enums\PermissionEnum;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The history of who has held what, read from both ends. It is the reason the
 * assignment is a table of its own rather than a column on the asset, so it is
 * worth a test that reads it the way somebody would.
 */
class AssetHistoryTest extends TestCase
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

        Queue::fake();
    }

    #[Test]
    public function it_reads_the_whole_history_of_a_piece_of_equipment(): void
    {
        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        $michael = Employee::factory()->create(['company_id' => $this->company->id]);
        $dwight = Employee::factory()->create(['company_id' => $this->company->id]);

        new CheckoutAsset(author: $this->author, asset: $asset, assignee: $michael, condition: AssetConditionEnum::New)->execute();
        new CheckinAsset(author: $this->author, asset: $asset, condition: AssetConditionEnum::Good)->execute();
        new CheckoutAsset(author: $this->author, asset: $asset, assignee: $dwight, condition: AssetConditionEnum::Good)->execute();

        $history = $asset->fresh()->assignments()->orderBy('id')->get();

        $this->assertCount(2, $history);
        $this->assertEquals($michael->id, $history[0]->assignee_id);
        $this->assertEquals(AssetConditionEnum::New, $history[0]->condition_at_checkout);
        $this->assertEquals(AssetConditionEnum::Good, $history[0]->condition_at_checkin);
        $this->assertNotNull($history[0]->returned_at);

        $this->assertEquals($dwight->id, $history[1]->assignee_id);
        $this->assertNull($history[1]->returned_at);
    }

    #[Test]
    public function it_reads_everything_a_colleague_holds_and_has_held(): void
    {
        $michael = Employee::factory()->create(['company_id' => $this->company->id]);
        $laptop = Asset::factory()->create(['company_id' => $this->company->id]);
        $phone = Asset::factory()->create(['company_id' => $this->company->id]);

        new CheckoutAsset(author: $this->author, asset: $laptop, assignee: $michael)->execute();
        new CheckoutAsset(author: $this->author, asset: $phone, assignee: $michael)->execute();
        new CheckinAsset(author: $this->author, asset: $phone)->execute();

        $held = $michael->assetAssignments()->get();

        $this->assertCount(2, $held);
        $this->assertCount(1, $held->whereNull('returned_at'));
        $this->assertEquals($laptop->id, $held->whereNull('returned_at')->first()->asset_id);
    }

    #[Test]
    public function it_reads_everything_that_has_been_assigned_to_an_office(): void
    {
        $room = Location::factory()->create(['company_id' => $this->company->id]);
        $display = Asset::factory()->create(['company_id' => $this->company->id]);

        new CheckoutAsset(author: $this->author, asset: $display, assignee: $room)->execute();

        $this->assertCount(1, $room->assetAssignments()->get());
        $this->assertEquals($display->id, $room->assetAssignments()->first()->asset_id);
    }

    #[Test]
    public function it_keeps_the_history_of_equipment_that_has_left_the_fleet(): void
    {
        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        $michael = Employee::factory()->create(['company_id' => $this->company->id]);

        new CheckoutAsset(author: $this->author, asset: $asset, assignee: $michael)->execute();
        new CheckinAsset(author: $this->author, asset: $asset)->execute();

        $asset->archived_at = now();
        $asset->save();

        $this->assertCount(1, $asset->fresh()->assignments);
        $this->assertCount(1, $michael->assetAssignments()->get());
    }
}
