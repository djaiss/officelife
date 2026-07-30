<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateCompany;
use App\Enums\PlanEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateCompanyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_company_and_its_owner(): void
    {
        Queue::fake();

        $company = new CreateCompany(
            name: 'Dunder Mifflin',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
            firstName: 'Michael',
            lastName: 'Scott',
        )->execute();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Dunder Mifflin',
            'slug' => 'dunder-mifflin',
            'plan' => PlanEnum::Free->value,
        ]);
        $this->assertDatabaseHas('users', [
            'company_id' => $company->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $this->assertEquals($company->users()->first()->id, $company->owner_user_id);
        $this->assertTrue(Hash::check('thatswhatshesaid', $company->owner->password));

        $this->assertDatabaseHas('employees', [
            'company_id' => $company->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
        $this->assertEquals('Michael Scott', $company->owner->employee->name);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::CompanyCreation
                && $job->company->id === $company->id
                && $job->user->id === $company->owner_user_id,
        );
    }

    #[Test]
    public function it_starts_the_company_on_a_trial(): void
    {
        Queue::fake();

        $company = new CreateCompany(
            name: 'Dunder Mifflin',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
            firstName: 'Michael',
            lastName: 'Scott',
        )->execute();

        $this->assertTrue($company->isOnTrial());
    }

    #[Test]
    public function it_accepts_a_plan(): void
    {
        Queue::fake();

        $company = new CreateCompany(
            name: 'Dunder Mifflin',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
            firstName: 'Michael',
            lastName: 'Scott',
            plan: PlanEnum::Business,
        )->execute();

        $this->assertEquals(PlanEnum::Business, $company->plan);
    }

    #[Test]
    public function it_makes_the_slug_unique(): void
    {
        Queue::fake();

        $first = new CreateCompany(
            name: 'Dunder Mifflin',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
            firstName: 'Michael',
            lastName: 'Scott',
        )->execute();

        $second = new CreateCompany(
            name: 'Dunder Mifflin',
            email: 'jim.halpert@dundermifflin.com',
            password: 'bearsbeatsbattlestar',
            firstName: 'Jim',
            lastName: 'Halpert',
        )->execute();

        $this->assertEquals('dunder-mifflin', $first->slug);
        $this->assertEquals('dunder-mifflin-2', $second->slug);
    }
}
