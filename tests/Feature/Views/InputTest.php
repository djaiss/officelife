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
    public function it_points_the_field_at_its_hint(): void
    {
        $html = Blade::render('<x-input id="company_name" :label="$l" :help="$h" />', [
            'l' => 'Company name',
            'h' => 'You can rename it later.',
        ]);

        $this->assertStringContainsString('aria-describedby="company_name-help"', $html);
        $this->assertStringContainsString('id="company_name-help"', $html);
    }

    #[Test]
    public function it_points_the_field_at_its_errors_and_marks_it_invalid(): void
    {
        $html = Blade::render('<x-input id="email" :error="$e" />', [
            'e' => ['That address is already taken.'],
        ]);

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="email-error"', $html);
        $this->assertStringContainsString('id="email-error"', $html);
    }

    #[Test]
    public function it_points_the_field_at_both_its_hint_and_its_errors(): void
    {
        $html = Blade::render('<x-input id="email" :help="$h" :error="$e" />', [
            'h' => 'We will never send you marketing emails.',
            'e' => ['That address is already taken.'],
        ]);

        $this->assertStringContainsString('aria-describedby="email-help email-error"', $html);
    }

    #[Test]
    public function it_leaves_a_healthy_field_undescribed(): void
    {
        $html = Blade::render('<x-input id="email" :label="$l" />', ['l' => 'Email address']);

        $this->assertStringNotContainsString('aria-describedby', $html);
        $this->assertStringNotContainsString('aria-invalid="true"', $html);
    }

    #[Test]
    public function it_ties_the_label_to_the_field(): void
    {
        $html = Blade::render('<x-input id="first_name" :label="$l" />', ['l' => 'First name']);

        $this->assertStringContainsString('for="first_name"', $html);
        $this->assertStringContainsString('id="first_name"', $html);
    }

    #[Test]
    public function it_renders_autocomplete_as_a_real_attribute(): void
    {
        $html = Blade::render('<x-input id="email" autocomplete="username" />');

        $this->assertStringContainsString('autocomplete="username"', $html);
        $this->assertStringNotContainsString('autocomplete=&quot;', $html);
    }
}
