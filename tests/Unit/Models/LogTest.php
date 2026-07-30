<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $log = Log::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($log->company()->exists());
        $this->assertEquals('Dunder Mifflin', $log->company->name);
    }

    #[Test]
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $log = Log::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($log->user()->exists());
        $this->assertEquals('michael.scott@dundermifflin.com', $log->user->email);
    }

    #[Test]
    public function it_casts_the_parameters_to_an_array(): void
    {
        $log = Log::factory()->create(['parameters' => ['name' => 'Dunder Mifflin']]);

        $this->assertEquals(['name' => 'Dunder Mifflin'], $log->refresh()->parameters);
    }
}
