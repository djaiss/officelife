<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateOffice;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateOfficeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_an_office(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $office = Office::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

        new UpdateOffice(
            author: $author,
            office: $office,
            name: 'Scranton business park',
            country: 'us',
            city: 'Scranton',
            address: '1725 Slough Avenue',
            timezone: 'America/New_York',
        )->execute();

        $office->refresh();

        $this->assertEquals('Scranton business park', $office->name);
        $this->assertEquals('US', $office->country);
        $this->assertEquals('Scranton', $office->city);
        $this->assertEquals('1725 Slough Avenue', $office->address);
        $this->assertEquals('America/New_York', $office->timezone);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::OfficeUpdated
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === ['name' => 'Scranton business park'],
        );
    }

    #[Test]
    public function it_empties_what_is_left_out(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $office = Office::factory()->create(['company_id' => $company->id]);

        new UpdateOffice(author: $author, office: $office, name: 'Utica branch')->execute();

        $office->refresh();

        $this->assertNull($office->country);
        $this->assertNull($office->city);
        $this->assertNull($office->address);
        $this->assertNull($office->timezone);
    }

    #[Test]
    public function it_lets_an_office_keep_its_own_name(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $office = Office::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

        new UpdateOffice(author: $author, office: $office, name: 'Scranton branch', city: 'Scranton')->execute();

        $office->refresh();

        $this->assertEquals('Scranton branch', $office->name);
        $this->assertEquals('Scranton', $office->city);
    }

    #[Test]
    public function it_promotes_an_office_to_head_office_and_demotes_the_one_that_held_it(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $scranton = Office::factory()->primary()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);
        $utica = Office::factory()->create(['company_id' => $company->id, 'name' => 'Utica branch']);

        new UpdateOffice(author: $author, office: $utica, name: 'Utica branch', isHeadOffice: true)->execute();

        $this->assertTrue($utica->refresh()->is_head_office);
        $this->assertFalse($scranton->refresh()->is_head_office);
    }

    #[Test]
    public function it_leaves_the_head_office_of_another_company_alone(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $office = Office::factory()->create(['company_id' => $company->id, 'name' => 'Utica branch']);
        $elsewhere = Office::factory()->primary()->create();

        new UpdateOffice(author: $author, office: $office, name: 'Utica branch', isHeadOffice: true)->execute();

        $this->assertTrue($elsewhere->refresh()->is_head_office);
    }

    #[Test]
    public function it_leaves_the_head_office_alone_when_the_box_is_not_ticked(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $office = Office::factory()->primary()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

        new UpdateOffice(author: $author, office: $office, name: 'Scranton business park')->execute();

        $this->assertTrue($office->refresh()->is_head_office);
    }

    #[Test]
    public function it_throws_when_an_archived_office_is_made_the_head_office(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $office = Office::factory()->archived()->create(['company_id' => $company->id, 'name' => 'Nashua branch']);

        new UpdateOffice(author: $author, office: $office, name: 'Nashua branch', isHeadOffice: true)->execute();
    }

    #[Test]
    public function it_throws_when_another_office_of_the_company_already_has_that_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        Office::factory()->create(['company_id' => $company->id, 'name' => 'Utica branch']);
        $office = Office::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

        new UpdateOffice(author: $author, office: $office, name: 'Utica branch')->execute();
    }

    #[Test]
    public function it_throws_when_the_name_is_blank(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $office = Office::factory()->create(['company_id' => $company->id]);

        new UpdateOffice(author: $author, office: $office, name: '  ')->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_change_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);
        $office = Office::factory()->create(['company_id' => $company->id]);

        new UpdateOffice(author: $author, office: $office, name: 'Scranton branch')->execute();
    }
}
