<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateAsset;
use App\Enums\ModuleEnum;
use App\Enums\OccurrenceTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\Company;
use App\Models\Occurrence;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateAssetTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $author;

    private AssetModel $assetModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->withModule(ModuleEnum::Assets)->create();
        $this->author = $this->grant(
            User::factory()->create(['company_id' => $this->company->id]),
            PermissionEnum::AssetManage,
        );
        $this->assetModel = AssetModel::factory()->create(['company_id' => $this->company->id]);
    }

    private function assetStatus(string $key): AssetStatus
    {
        return AssetStatus::query()->where('key', $key)->firstOrFail();
    }

    #[Test]
    public function it_changes_a_piece_of_equipment(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_model_id' => $this->assetModel->id,
        ]);

        new UpdateAsset(
            author: $this->author,
            asset: $asset,
            assetModel: $this->assetModel,
            status: $this->assetStatus(AssetStatus::PENDING),
            assetTag: 'OL-LAPTOP-9999',
            name: 'Reception spare',
        )->execute();

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'asset_tag' => 'OL-LAPTOP-9999',
            'name' => 'Reception spare',
            'status_id' => $this->assetStatus(AssetStatus::PENDING)->id,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AssetUpdate,
        );
    }

    #[Test]
    public function it_says_so_when_equipment_is_reported_lost(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_model_id' => $this->assetModel->id,
        ]);

        new UpdateAsset(
            author: $this->author,
            asset: $asset,
            assetModel: $this->assetModel,
            status: $this->assetStatus(AssetStatus::LOST),
            assetTag: $asset->asset_tag,
        )->execute();

        $this->assertDatabaseHas('occurrences', [
            'company_id' => $this->company->id,
            'type' => OccurrenceTypeEnum::AssetReportedLost->value,
            'subject_type' => Asset::class,
            'subject_id' => $asset->id,
        ]);
    }

    #[Test]
    public function it_says_nothing_when_the_equipment_was_already_lost(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_model_id' => $this->assetModel->id,
            'status_id' => $this->assetStatus(AssetStatus::LOST)->id,
        ]);

        new UpdateAsset(
            author: $this->author,
            asset: $asset,
            assetModel: $this->assetModel,
            status: $this->assetStatus(AssetStatus::LOST),
            assetTag: $asset->asset_tag,
        )->execute();

        $this->assertEquals(
            0,
            Occurrence::query()->where('type', OccurrenceTypeEnum::AssetReportedLost)->count(),
        );
    }

    #[Test]
    public function it_says_nothing_for_any_other_change_of_status(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_model_id' => $this->assetModel->id,
        ]);

        new UpdateAsset(
            author: $this->author,
            asset: $asset,
            assetModel: $this->assetModel,
            status: $this->assetStatus(AssetStatus::AWAITING_REPAIR),
            assetTag: $asset->asset_tag,
        )->execute();

        $this->assertEquals(
            0,
            Occurrence::query()->where('type', OccurrenceTypeEnum::AssetReportedLost)->count(),
        );
    }

    #[Test]
    public function it_throws_when_the_tag_belongs_to_other_equipment_of_the_company(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        Asset::factory()->create(['company_id' => $this->company->id, 'asset_tag' => 'OL-TAKEN']);
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_model_id' => $this->assetModel->id,
        ]);

        new UpdateAsset(
            author: $this->author,
            asset: $asset,
            assetModel: $this->assetModel,
            status: $this->assetStatus(AssetStatus::READY_TO_DEPLOY),
            assetTag: 'OL-TAKEN',
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_manage_equipment(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_model_id' => $this->assetModel->id,
        ]);

        new UpdateAsset(
            author: User::factory()->create(['company_id' => $this->company->id]),
            asset: $asset,
            assetModel: $this->assetModel,
            status: $this->assetStatus(AssetStatus::READY_TO_DEPLOY),
            assetTag: $asset->asset_tag,
        )->execute();
    }
}
