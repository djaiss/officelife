<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateAsset;
use App\Enums\ModuleEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateAssetTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $author;

    private AssetModel $assetModel;

    private AssetStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->withModule(ModuleEnum::Assets)->create();
        $this->author = $this->grant(
            User::factory()->create(['company_id' => $this->company->id]),
            PermissionEnum::AssetManage,
        );
        $this->assetModel = AssetModel::factory()->create(['company_id' => $this->company->id]);
        $this->status = AssetStatus::query()->where('key', AssetStatus::READY_TO_DEPLOY)->firstOrFail();
    }

    #[Test]
    public function it_records_a_piece_of_equipment(): void
    {
        Queue::fake();

        $scranton = Location::factory()->create(['company_id' => $this->company->id]);

        $asset = new CreateAsset(
            author: $this->author,
            company: $this->company,
            assetModel: $this->assetModel,
            status: $this->status,
            assetTag: 'OL-LAPTOP-0042',
            serialNumber: 'C02XY1234567',
            name: 'The one Michael drops',
            defaultLocation: $scranton,
            purchaseCost: 249900,
        )->execute();

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'company_id' => $this->company->id,
            'asset_tag' => 'OL-LAPTOP-0042',
            'serial_number' => 'C02XY1234567',
            'default_location_id' => $scranton->id,
            'current_location_id' => $scranton->id,
            'purchase_cost' => 249900,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AssetCreation
                && $job->parameters === ['tag' => 'OL-LAPTOP-0042'],
        );
    }

    #[Test]
    public function it_records_equipment_with_no_serial_number(): void
    {
        Queue::fake();

        $asset = new CreateAsset(
            author: $this->author,
            company: $this->company,
            assetModel: $this->assetModel,
            status: $this->status,
            assetTag: 'OL-LAPTOP-0043',
        )->execute();

        $this->assertNull($asset->serial_number);
    }

    #[Test]
    public function it_lets_two_pieces_of_equipment_share_a_serial_number(): void
    {
        Queue::fake();

        foreach (['OL-0001', 'OL-0002'] as $tag) {
            new CreateAsset(
                author: $this->author,
                company: $this->company,
                assetModel: $this->assetModel,
                status: $this->status,
                assetTag: $tag,
                serialNumber: 'SAME-SERIAL',
            )->execute();
        }

        $this->assertEquals(2, Asset::query()->where('serial_number', 'SAME-SERIAL')->count());
    }

    #[Test]
    public function it_throws_when_the_tag_is_already_taken_in_the_company(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        Asset::factory()->create(['company_id' => $this->company->id, 'asset_tag' => 'OL-LAPTOP-0042']);

        new CreateAsset(
            author: $this->author,
            company: $this->company,
            assetModel: $this->assetModel,
            status: $this->status,
            assetTag: 'OL-LAPTOP-0042',
        )->execute();
    }

    #[Test]
    public function it_lets_another_company_use_the_same_tag(): void
    {
        Queue::fake();

        Asset::factory()->create(['asset_tag' => 'OL-LAPTOP-0042']);

        $asset = new CreateAsset(
            author: $this->author,
            company: $this->company,
            assetModel: $this->assetModel,
            status: $this->status,
            assetTag: 'OL-LAPTOP-0042',
        )->execute();

        $this->assertEquals('OL-LAPTOP-0042', $asset->asset_tag);
    }

    #[Test]
    public function it_throws_when_the_tag_is_blank(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CreateAsset(
            author: $this->author,
            company: $this->company,
            assetModel: $this->assetModel,
            status: $this->status,
            assetTag: '  ',
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_model_belongs_to_another_company(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CreateAsset(
            author: $this->author,
            company: $this->company,
            assetModel: AssetModel::factory()->create(),
            status: $this->status,
            assetTag: 'OL-LAPTOP-0044',
        )->execute();
    }

    #[Test]
    public function it_accepts_a_status_of_its_own_company_and_refuses_one_of_another(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        new CreateAsset(
            author: $this->author,
            company: $this->company,
            assetModel: $this->assetModel,
            status: AssetStatus::factory()->create(),
            assetTag: 'OL-LAPTOP-0045',
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_manage_equipment(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CreateAsset(
            author: User::factory()->create(['company_id' => $this->company->id]),
            company: $this->company,
            assetModel: $this->assetModel,
            status: $this->status,
            assetTag: 'OL-LAPTOP-0046',
        )->execute();
    }
}
