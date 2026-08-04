<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ArchiveAsset;
use App\Actions\RestoreAsset;
use App\Enums\ModuleEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArchiveAssetTest extends TestCase
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
    public function it_takes_equipment_out_of_the_fleet(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);

        $asset = new ArchiveAsset(author: $this->author, asset: $asset)->execute();

        $this->assertTrue($asset->fresh()->isArchived());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AssetArchive,
        );
    }

    #[Test]
    public function it_keeps_the_history_of_archived_equipment(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        AssetAssignment::factory()->returned()->to($employee)->create(['asset_id' => $asset->id]);

        new ArchiveAsset(author: $this->author, asset: $asset)->execute();

        $this->assertCount(1, $asset->fresh()->assignments);
        $this->assertCount(1, $employee->assetAssignments()->get());
    }

    #[Test]
    public function it_refuses_equipment_somebody_still_has(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        AssetAssignment::factory()->create(['asset_id' => $asset->id]);

        new ArchiveAsset(author: $this->author, asset: $asset)->execute();
    }

    #[Test]
    public function it_brings_equipment_back_into_the_fleet(): void
    {
        Queue::fake();

        $asset = Asset::factory()->archived()->create(['company_id' => $this->company->id]);

        new RestoreAsset(author: $this->author, asset: $asset)->execute();

        $this->assertFalse($asset->fresh()->isArchived());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AssetRestoration,
        );
    }

    #[Test]
    public function it_throws_when_the_author_may_not_manage_equipment(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);

        new ArchiveAsset(
            author: User::factory()->create(['company_id' => $this->company->id]),
            asset: $asset,
        )->execute();
    }
}
