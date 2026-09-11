<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ArchiveOffice;
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

class ArchiveOfficeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_archives_an_office(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $office = Office::factory()->create(['company_id' => $company->id, 'name' => 'Utica branch']);

        $archived = new ArchiveOffice(author: $author, office: $office)->execute();

        $this->assertInstanceOf(Office::class, $archived);

        $office->refresh();

        $this->assertTrue($office->isArchived());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::OfficeArchived
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === ['name' => 'Utica branch'],
        );
    }

    #[Test]
    public function it_takes_the_head_office_badge_away(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $office = Office::factory()->primary()->create(['company_id' => $company->id]);

        new ArchiveOffice(author: $author, office: $office)->execute();

        $office->refresh();

        $this->assertFalse($office->is_head_office);
    }

    #[Test]
    public function it_throws_when_the_author_may_not_change_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);
        $office = Office::factory()->create(['company_id' => $company->id]);

        new ArchiveOffice(author: $author, office: $office)->execute();
    }

    #[Test]
    public function it_throws_when_the_office_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $author = $this->grant(User::factory()->create(), PermissionEnum::CompanyManage);
        $office = Office::factory()->create();

        new ArchiveOffice(author: $author, office: $office)->execute();
    }
}
