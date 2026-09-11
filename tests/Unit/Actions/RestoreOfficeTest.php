<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RestoreOffice;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreOfficeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reopens_an_office(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $office = Office::factory()->archived()->create(['company_id' => $company->id, 'name' => 'Nashua branch']);

        $restored = new RestoreOffice(author: $author, office: $office)->execute();

        $this->assertInstanceOf(Office::class, $restored);

        $office->refresh();

        $this->assertFalse($office->isArchived());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::OfficeRestored
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === ['name' => 'Nashua branch'],
        );
    }

    #[Test]
    public function it_does_not_give_the_head_office_badge_back(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $office = Office::factory()->archived()->create(['company_id' => $company->id]);

        new RestoreOffice(author: $author, office: $office)->execute();

        $office->refresh();

        $this->assertFalse($office->is_head_office);
    }

    #[Test]
    public function it_throws_when_the_author_may_not_change_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);
        $office = Office::factory()->archived()->create(['company_id' => $company->id]);

        new RestoreOffice(author: $author, office: $office)->execute();
    }

    #[Test]
    public function it_throws_when_the_office_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $author = $this->grant(User::factory()->create(), PermissionEnum::CompanyManage);
        $office = Office::factory()->archived()->create();

        new RestoreOffice(author: $author, office: $office)->execute();
    }
}
