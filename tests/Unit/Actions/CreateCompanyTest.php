<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateCompany;
use App\Enums\PlanEnum;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateCompanyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_company_and_its_owner(): void
    {
        $company = new CreateCompany(
            name: 'Dunder Mifflin',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
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
        $this->assertTrue(Hash::check('thatswhatshesaid', $company->owner->password_hash));
    }

    #[Test]
    public function it_starts_the_company_on_a_trial(): void
    {
        $company = new CreateCompany(
            name: 'Dunder Mifflin',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $this->assertTrue($company->isOnTrial());
    }

    #[Test]
    public function it_accepts_a_plan(): void
    {
        $company = new CreateCompany(
            name: 'Dunder Mifflin',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
            plan: PlanEnum::Business,
        )->execute();

        $this->assertEquals(PlanEnum::Business, $company->plan);
    }

    #[Test]
    public function it_makes_the_slug_unique(): void
    {
        $first = new CreateCompany(
            name: 'Dunder Mifflin',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $second = new CreateCompany(
            name: 'Dunder Mifflin',
            email: 'jim.halpert@dundermifflin.com',
            password: 'bearsbeatsbattlestar',
        )->execute();

        $this->assertEquals('dunder-mifflin', $first->slug);
        $this->assertEquals('dunder-mifflin-2', $second->slug);
    }
}
