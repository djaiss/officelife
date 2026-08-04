<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DisableModule;
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

class DisableModuleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_turns_a_module_off(): void
    {
        Queue::fake();

        $company = Company::factory()->withModule(ModuleEnum::Assets)->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);

        $company = new DisableModule(author: $author, company: $company, module: ModuleEnum::Assets)->execute();

        $this->assertFalse($company->fresh()->hasModule(ModuleEnum::Assets));

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ModuleDisabled
                && $job->parameters === ['name' => 'Assets'],
        );
    }

    #[Test]
    public function it_says_nothing_when_the_module_was_not_on(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);

        new DisableModule(author: $author, company: $company, module: ModuleEnum::Assets)->execute();

        $this->assertEquals([], $company->fresh()->settings['modules']);
    }

    #[Test]
    public function it_throws_when_the_author_may_not_change_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->withModule(ModuleEnum::Assets)->create();
        $author = User::factory()->create(['company_id' => $company->id]);

        new DisableModule(author: $author, company: $company, module: ModuleEnum::Assets)->execute();
    }
}
