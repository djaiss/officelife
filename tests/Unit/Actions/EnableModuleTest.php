<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\EnableModule;
use App\Enums\ModuleEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnableModuleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_turns_a_module_on(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);

        $this->assertFalse($company->hasModule(ModuleEnum::Assets));

        $company = new EnableModule(author: $author, company: $company, module: ModuleEnum::Assets)->execute();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertTrue($company->fresh()->hasModule(ModuleEnum::Assets));

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ModuleEnabled
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === ['name' => 'Assets'],
        );
    }

    #[Test]
    public function it_leaves_the_other_settings_of_the_company_alone(): void
    {
        Queue::fake();

        $company = Company::factory()->create(['settings' => ['theme' => 'dunder-mifflin']]);
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);

        new EnableModule(author: $author, company: $company, module: ModuleEnum::Assets)->execute();

        $this->assertEquals('dunder-mifflin', $company->fresh()->settings['theme']);
    }

    #[Test]
    public function it_gives_the_assets_module_a_catalogue_to_start_from(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);

        new EnableModule(author: $author, company: $company, module: ModuleEnum::Assets)->execute();

        $this->assertCount(7, $company->assetCategories()->get());
        $this->assertContains('Laptops', $company->assetCategories()->get()->pluck('name')->all());
    }

    #[Test]
    public function it_leaves_the_catalogue_alone_when_the_module_is_turned_back_on(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);

        new EnableModule(author: $author, company: $company, module: ModuleEnum::Assets)->execute();
        $company->assetCategories()->where('name_translation_key', 'Tablets')->delete();
        new EnableModule(author: $author, company: $company, module: ModuleEnum::Assets)->execute();

        $this->assertCount(6, $company->assetCategories()->get());
    }

    #[Test]
    public function it_does_not_list_a_module_twice_when_it_is_already_on(): void
    {
        Queue::fake();

        $company = Company::factory()->withModule(ModuleEnum::Assets)->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);

        new EnableModule(author: $author, company: $company, module: ModuleEnum::Assets)->execute();

        $this->assertEquals(['assets'], $company->fresh()->settings['modules']);
    }

    #[Test]
    public function it_throws_when_the_author_may_not_change_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);

        new EnableModule(author: $author, company: $company, module: ModuleEnum::Assets)->execute();
    }

    #[Test]
    public function it_throws_when_the_company_belongs_to_somebody_else(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $dunderMifflin = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::CompanyManage);

        new EnableModule(author: $author, company: Company::factory()->create(), module: ModuleEnum::Assets)->execute();
    }
}
