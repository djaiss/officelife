<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateAssetModel;
use App\Actions\DestroyAssetModel;
use App\Actions\UpdateAssetModel;
use App\Enums\AssetCategoryTypeEnum;
use App\Enums\ModuleEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Company;
use App\Models\Manufacturer;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetModelActionsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $author;

    private Manufacturer $manufacturer;

    private AssetCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->withModule(ModuleEnum::Assets)->create();
        $this->author = $this->grant(
            User::factory()->create(['company_id' => $this->company->id]),
            PermissionEnum::AssetManage,
        );
        $this->manufacturer = Manufacturer::factory()->create(['company_id' => $this->company->id]);
        $this->category = AssetCategory::factory()->create(['company_id' => $this->company->id]);
    }

    #[Test]
    public function it_adds_a_model(): void
    {
        Queue::fake();

        $assetModel = new CreateAssetModel(
            author: $this->author,
            company: $this->company,
            manufacturer: $this->manufacturer,
            category: $this->category,
            name: 'Apple MacBook Pro 14-inch M4 Pro',
            modelNumber: 'MX2H3',
            usefulLifeMonths: 48,
        )->execute();

        $this->assertInstanceOf(AssetModel::class, $assetModel);
        $this->assertDatabaseHas('asset_models', [
            'id' => $assetModel->id,
            'company_id' => $this->company->id,
            'manufacturer_id' => $this->manufacturer->id,
            'asset_category_id' => $this->category->id,
            'name' => 'Apple MacBook Pro 14-inch M4 Pro',
            'useful_life_months' => 48,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AssetModelCreation,
        );
    }

    #[Test]
    public function it_throws_when_the_manufacturer_belongs_to_another_company(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CreateAssetModel(
            author: $this->author,
            company: $this->company,
            manufacturer: Manufacturer::factory()->create(),
            category: $this->category,
            name: 'A laptop',
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_category_belongs_to_another_company(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CreateAssetModel(
            author: $this->author,
            company: $this->company,
            manufacturer: $this->manufacturer,
            category: AssetCategory::factory()->create(),
            name: 'A laptop',
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_category_is_of_a_family_nothing_is_built_for(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $consumables = AssetCategory::factory()->create([
            'company_id' => $this->company->id,
            'type' => AssetCategoryTypeEnum::Consumable,
        ]);

        new CreateAssetModel(
            author: $this->author,
            company: $this->company,
            manufacturer: $this->manufacturer,
            category: $consumables,
            name: 'Toner',
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_company_already_has_that_model(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AssetModel::factory()->create(['company_id' => $this->company->id, 'name' => 'A laptop']);

        new CreateAssetModel(
            author: $this->author,
            company: $this->company,
            manufacturer: $this->manufacturer,
            category: $this->category,
            name: 'A laptop',
        )->execute();
    }

    #[Test]
    public function it_changes_a_model(): void
    {
        Queue::fake();

        $assetModel = AssetModel::factory()->create(['company_id' => $this->company->id]);

        new UpdateAssetModel(
            author: $this->author,
            assetModel: $assetModel,
            manufacturer: $this->manufacturer,
            category: $this->category,
            name: 'Dell XPS 13',
            isRequestable: true,
        )->execute();

        $this->assertDatabaseHas('asset_models', [
            'id' => $assetModel->id,
            'name' => 'Dell XPS 13',
            'manufacturer_id' => $this->manufacturer->id,
            'is_requestable' => true,
        ]);
    }

    #[Test]
    public function it_deletes_a_model_the_company_owns_nothing_of(): void
    {
        Queue::fake();

        $assetModel = AssetModel::factory()->create(['company_id' => $this->company->id]);

        new DestroyAssetModel(author: $this->author, assetModel: $assetModel)->execute();

        $this->assertModelMissing($assetModel);
    }

    #[Test]
    public function it_refuses_to_delete_a_model_the_company_still_owns_equipment_of(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        $assetModel = AssetModel::factory()->create(['company_id' => $this->company->id]);
        Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_model_id' => $assetModel->id,
        ]);

        new DestroyAssetModel(author: $this->author, assetModel: $assetModel)->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_manage_equipment(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CreateAssetModel(
            author: User::factory()->create(['company_id' => $this->company->id]),
            company: $this->company,
            manufacturer: $this->manufacturer,
            category: $this->category,
            name: 'A laptop',
        )->execute();
    }
}
