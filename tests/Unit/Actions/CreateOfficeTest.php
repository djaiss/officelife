<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateOffice;
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

class CreateOfficeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_an_office(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);

        $office = new CreateOffice(
            author: $author,
            company: $company,
            name: 'Scranton branch',
            country: 'US',
            city: 'Scranton',
            address: '1725 Slough Avenue',
            timezone: 'America/New_York',
        )->execute();

        $this->assertInstanceOf(Office::class, $office);
        $this->assertDatabaseHas('offices', [
            'id' => $office->id,
            'company_id' => $company->id,
            'name' => 'Scranton branch',
            'country' => 'US',
            'city' => 'Scranton',
            'address' => '1725 Slough Avenue',
            'timezone' => 'America/New_York',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::OfficeCreated
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === ['name' => 'Scranton branch'],
        );
    }

    #[Test]
    public function it_creates_an_office_with_nothing_but_a_name(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);

        $office = new CreateOffice(author: $author, company: $company, name: 'Utica branch')->execute();

        $this->assertDatabaseHas('offices', [
            'id' => $office->id,
            'name' => 'Utica branch',
            'country' => null,
            'city' => null,
            'address' => null,
            'timezone' => null,
        ]);
    }

    #[Test]
    public function it_uppercases_the_country_and_strips_html_from_the_name(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);

        $office = new CreateOffice(
            author: $author,
            company: $company,
            name: '<b>Nashua branch</b>',
            country: 'us',
        )->execute();

        $this->assertEquals('Nashua branch', $office->name);
        $this->assertEquals('US', $office->country);
    }

    #[Test]
    public function it_throws_when_the_name_is_blank(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);

        new CreateOffice(author: $author, company: $company, name: '  ')->execute();
    }

    #[Test]
    public function it_throws_when_the_company_already_has_an_office_of_that_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        Office::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

        new CreateOffice(author: $author, company: $company, name: 'Scranton branch')->execute();
    }

    #[Test]
    public function it_lets_another_company_use_the_same_name(): void
    {
        Queue::fake();

        $dunderMifflin = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::CompanyManage);
        Office::factory()->create(['company_id' => Company::factory()->create()->id, 'name' => 'Scranton branch']);

        $office = new CreateOffice(author: $author, company: $dunderMifflin, name: 'Scranton branch')->execute();

        $this->assertEquals('Scranton branch', $office->name);
    }

    #[Test]
    public function it_throws_when_the_author_may_not_change_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);

        new CreateOffice(author: $author, company: $company, name: 'Scranton branch')->execute();
    }
}
