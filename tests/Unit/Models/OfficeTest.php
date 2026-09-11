<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Office;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfficeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $company = Company::factory()->create();
        $office = Office::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($office->company()->exists());
        $this->assertEquals($company->id, $office->company->id);
    }

    #[Test]
    public function it_knows_whether_it_has_been_archived(): void
    {
        $open = Office::factory()->make();
        $closed = Office::factory()->archived()->make();

        $this->assertFalse($open->isArchived());
        $this->assertTrue($closed->isArchived());
    }

    #[Test]
    public function it_casts_the_day_it_was_archived_and_the_head_office_flag(): void
    {
        $office = Office::factory()->archived()->primary()->create();

        $this->assertInstanceOf(Carbon::class, $office->archived_at);
        $this->assertIsBool($office->is_head_office);
    }
}
