<?php

declare(strict_types=1);

namespace Tests\Feature\Views;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InputTest extends TestCase
{
    #[Test]
    public function it_keeps_password_managers_away_from_an_ordinary_field(): void
    {
        $html = Blade::render('<x-input id="company_name" />');

        $this->assertStringContainsString('data-1p-ignore', $html);
    }

    #[Test]
    public function it_lets_a_field_opt_into_password_managers(): void
    {
        $html = Blade::render('<x-input id="email" allowPasswordManager />');

        $this->assertStringNotContainsString('data-1p-ignore', $html);
    }

    #[Test]
    public function it_renders_autocomplete_as_a_real_attribute(): void
    {
        $html = Blade::render('<x-input id="email" autocomplete="username" />');

        $this->assertStringContainsString('autocomplete="username"', $html);
        $this->assertStringNotContainsString('autocomplete=&quot;', $html);
    }
}
