<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\Slug;
use App\Models\Company;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SlugTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_record_under_the_slug_of_its_name(): void
    {
        $company = Slug::write(
            name: 'Dunder Mifflin',
            fallback: 'company',
            taken: fn (string $slug): bool => Company::query()->where('slug', $slug)->exists(),
            write: fn (string $slug): Company => Company::factory()->create(['slug' => $slug]),
        );

        $this->assertEquals('dunder-mifflin', $company->slug);
    }

    #[Test]
    public function it_falls_back_when_the_name_makes_no_slug(): void
    {
        $company = Slug::write(
            name: '!!!',
            fallback: 'company',
            taken: fn (string $slug): bool => Company::query()->where('slug', $slug)->exists(),
            write: fn (string $slug): Company => Company::factory()->create(['slug' => $slug]),
        );

        $this->assertEquals('company', $company->slug);
    }

    #[Test]
    public function it_numbers_from_one_when_the_slug_is_held(): void
    {
        Company::factory()->create(['slug' => 'dunder-mifflin']);

        $second = Slug::write(
            name: 'Dunder Mifflin',
            fallback: 'company',
            taken: fn (string $slug): bool => Company::query()->where('slug', $slug)->exists(),
            write: fn (string $slug): Company => Company::factory()->create(['slug' => $slug]),
        );

        $third = Slug::write(
            name: 'Dunder Mifflin',
            fallback: 'company',
            taken: fn (string $slug): bool => Company::query()->where('slug', $slug)->exists(),
            write: fn (string $slug): Company => Company::factory()->create(['slug' => $slug]),
        );

        $this->assertEquals('dunder-mifflin-1', $second->slug);
        $this->assertEquals('dunder-mifflin-2', $third->slug);
    }

    #[Test]
    public function it_writes_again_under_the_next_number_when_the_index_refuses_the_row(): void
    {
        Company::factory()->create(['slug' => 'dunder-mifflin']);

        $looks = 0;
        $taken = function (string $slug) use (&$looks): bool {
            $looks++;

            if ($looks === 1) {
                return false;
            }

            return Company::query()->where('slug', $slug)->exists();
        };

        $company = Slug::write(
            name: 'Dunder Mifflin',
            fallback: 'company',
            taken: $taken,
            write: fn (string $slug): Company => Company::factory()->create(['slug' => $slug]),
        );

        $this->assertEquals('dunder-mifflin-1', $company->slug);
    }

    #[Test]
    public function it_gives_up_when_the_index_keeps_refusing_the_row(): void
    {
        Company::factory()->create(['slug' => 'dunder-mifflin']);

        $this->expectException(UniqueConstraintViolationException::class);

        Slug::write(
            name: 'Dunder Mifflin',
            fallback: 'company',
            taken: fn (string $slug): bool => false,
            write: fn (string $slug): Company => Company::factory()->create(['slug' => $slug]),
        );
    }
}
