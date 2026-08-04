<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateAssetCategory;
use App\Actions\DestroyAssetCategory;
use App\Actions\UpdateAssetCategory;
use App\Enums\AssetCategoryTypeEnum;
use App\Enums\ModuleEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetCategoryActionsTest extends TestCase
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
            PermissionEnum::AssetManage,
        );
    }

    #[Test]
    public function it_adds_a_category(): void
    {
        Queue::fake();

        $category = new CreateAssetCategory(
            author: $this->author,
            company: $this->company,
            name: 'Laptops',
        )->execute();

        $this->assertInstanceOf(AssetCategory::class, $category);
        $this->assertDatabaseHas('asset_categories', [
            'id' => $category->id,
            'company_id' => $this->company->id,
            'name' => 'Laptops',
            'type' => 'asset',
            'requires_acceptance' => false,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AssetCategoryCreation,
        );
    }

    #[Test]
    public function it_adds_a_category_of_a_family_nothing_is_built_for_yet(): void
    {
        Queue::fake();

        $category = new CreateAssetCategory(
            author: $this->author,
            company: $this->company,
            name: 'Cables',
            type: AssetCategoryTypeEnum::Consumable,
        )->execute();

        $this->assertEquals(AssetCategoryTypeEnum::Consumable, $category->type);
    }

    #[Test]
    public function it_throws_when_a_category_asks_for_acceptance_without_saying_what(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CreateAssetCategory(
            author: $this->author,
            company: $this->company,
            name: 'Security badges',
            requiresAcceptance: true,
        )->execute();
    }

    #[Test]
    public function it_adds_a_category_that_asks_for_acceptance(): void
    {
        Queue::fake();

        $category = new CreateAssetCategory(
            author: $this->author,
            company: $this->company,
            name: 'Security badges',
            requiresAcceptance: true,
            eulaText: 'Do not lend it to anybody.',
        )->execute();

        $this->assertTrue($category->requires_acceptance);
        $this->assertEquals('Do not lend it to anybody.', $category->eula_text);
    }

    #[Test]
    public function it_throws_when_the_company_already_has_that_category(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AssetCategory::factory()->create(['company_id' => $this->company->id, 'name' => 'Laptops']);

        new CreateAssetCategory(author: $this->author, company: $this->company, name: 'Laptops')->execute();
    }

    #[Test]
    public function it_changes_a_category(): void
    {
        Queue::fake();

        $category = AssetCategory::factory()->create(['company_id' => $this->company->id]);

        new UpdateAssetCategory(
            author: $this->author,
            category: $category,
            name: 'Phones',
            sendCheckoutEmail: true,
        )->execute();

        $this->assertDatabaseHas('asset_categories', [
            'id' => $category->id,
            'name' => 'Phones',
            'send_checkout_email' => true,
        ]);
    }

    #[Test]
    public function it_makes_a_category_theirs_when_they_rename_one_we_shipped(): void
    {
        Queue::fake();

        $category = AssetCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => null,
            'name_translation_key' => 'Laptops',
        ]);

        new UpdateAssetCategory(
            author: $this->author,
            category: $category,
            name: 'Work machines',
        )->execute();

        $this->assertDatabaseHas('asset_categories', [
            'id' => $category->id,
            'name' => 'Work machines',
            'name_translation_key' => null,
        ]);

        App::setLocale('fr_FR');
        $this->assertEquals('Work machines', $category->fresh()->name);
    }

    #[Test]
    public function it_refuses_a_name_that_a_category_we_shipped_already_reads_as(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AssetCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => null,
            'name_translation_key' => 'Laptops',
        ]);

        new CreateAssetCategory(author: $this->author, company: $this->company, name: 'Laptops')->execute();
    }

    #[Test]
    public function it_deletes_a_category_nothing_is_filed_under(): void
    {
        Queue::fake();

        $category = AssetCategory::factory()->create(['company_id' => $this->company->id]);

        new DestroyAssetCategory(author: $this->author, category: $category)->execute();

        $this->assertModelMissing($category);
    }

    #[Test]
    public function it_refuses_to_delete_a_category_with_equipment_filed_under_it(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        $category = AssetCategory::factory()->create(['company_id' => $this->company->id]);
        AssetModel::factory()->create([
            'company_id' => $this->company->id,
            'asset_category_id' => $category->id,
        ]);

        new DestroyAssetCategory(author: $this->author, category: $category)->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_manage_equipment(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CreateAssetCategory(
            author: User::factory()->create(['company_id' => $this->company->id]),
            company: $this->company,
            name: 'Laptops',
        )->execute();
    }
}
