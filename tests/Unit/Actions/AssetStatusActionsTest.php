<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateAssetStatus;
use App\Actions\DestroyAssetStatus;
use App\Actions\UpdateAssetStatus;
use App\Enums\AssetStatusTypeEnum;
use App\Enums\ModuleEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Asset;
use App\Models\AssetStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetStatusActionsTest extends TestCase
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
    public function it_adds_a_status_of_its_own_declaring_one_of_the_four_types(): void
    {
        Queue::fake();

        $status = new CreateAssetStatus(
            author: $this->author,
            company: $this->company,
            name: 'Awaiting wipe',
            type: AssetStatusTypeEnum::Undeployable,
            color: '#7c3aed',
        )->execute();

        $this->assertInstanceOf(AssetStatus::class, $status);
        $this->assertDatabaseHas('asset_statuses', [
            'id' => $status->id,
            'company_id' => $this->company->id,
            'name' => 'Awaiting wipe',
            'type' => 'undeployable',
            'key' => null,
            'is_system' => false,
        ]);
        $this->assertFalse($status->isDeployable());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AssetStatusCreation,
        );
    }

    #[Test]
    public function it_never_gives_a_company_status_a_key(): void
    {
        Queue::fake();

        $status = new CreateAssetStatus(
            author: $this->author,
            company: $this->company,
            name: 'Gone walkabout',
            type: AssetStatusTypeEnum::Undeployable,
        )->execute();

        $this->assertNull($status->key);
        $this->assertFalse($status->meansLost());
    }

    #[Test]
    public function it_throws_when_the_name_clashes_with_a_status_every_company_gets(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CreateAssetStatus(
            author: $this->author,
            company: $this->company,
            name: 'Lost',
            type: AssetStatusTypeEnum::Undeployable,
        )->execute();
    }

    #[Test]
    public function it_changes_a_status_the_company_added(): void
    {
        Queue::fake();

        $status = AssetStatus::factory()->create(['company_id' => $this->company->id]);

        new UpdateAssetStatus(
            author: $this->author,
            status: $status,
            name: 'In transit',
            type: AssetStatusTypeEnum::Pending,
        )->execute();

        $this->assertDatabaseHas('asset_statuses', [
            'id' => $status->id,
            'name' => 'In transit',
            'type' => 'pending',
        ]);
    }

    #[Test]
    public function it_refuses_to_change_a_status_every_company_gets(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $lost = AssetStatus::query()->where('key', AssetStatus::LOST)->firstOrFail();

        new UpdateAssetStatus(
            author: $this->author,
            status: $lost,
            name: 'Vanished',
            type: AssetStatusTypeEnum::Undeployable,
        )->execute();
    }

    #[Test]
    public function it_refuses_to_delete_a_status_every_company_gets(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $lost = AssetStatus::query()->where('key', AssetStatus::LOST)->firstOrFail();

        new DestroyAssetStatus(author: $this->author, status: $lost)->execute();
    }

    #[Test]
    public function it_deletes_a_status_nothing_is_in(): void
    {
        Queue::fake();

        $status = AssetStatus::factory()->create(['company_id' => $this->company->id]);

        new DestroyAssetStatus(author: $this->author, status: $status)->execute();

        $this->assertModelMissing($status);
    }

    #[Test]
    public function it_refuses_to_delete_a_status_equipment_is_still_in(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        $status = AssetStatus::factory()->create(['company_id' => $this->company->id]);
        Asset::factory()->create(['company_id' => $this->company->id, 'status_id' => $status->id]);

        new DestroyAssetStatus(author: $this->author, status: $status)->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_manage_equipment(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CreateAssetStatus(
            author: User::factory()->create(['company_id' => $this->company->id]),
            company: $this->company,
            name: 'In transit',
            type: AssetStatusTypeEnum::Pending,
        )->execute();
    }
}
