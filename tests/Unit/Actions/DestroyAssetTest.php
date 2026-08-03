<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyAsset;
use App\Enums\ModuleEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyAssetTest extends TestCase
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
    public function it_deletes_equipment_nobody_has_ever_held(): void
    {
        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);

        new DestroyAsset(author: $this->author, asset: $asset)->execute();

        $this->assertModelMissing($asset);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AssetDeletion,
        );
    }

    #[Test]
    public function it_refuses_equipment_somebody_has_held(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        $asset = Asset::factory()->create(['company_id' => $this->company->id]);
        AssetAssignment::factory()->returned()->create(['asset_id' => $asset->id]);

        new DestroyAsset(author: $this->author, asset: $asset)->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_manage_equipment(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyAsset(
            author: User::factory()->create(['company_id' => $this->company->id]),
            asset: Asset::factory()->create(['company_id' => $this->company->id]),
        )->execute();
    }
}
