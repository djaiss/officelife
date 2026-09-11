<?php

declare(strict_types=1);

namespace App\Enums;

enum ScopeEnum: string
{
    case Self = 'self';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Self => 'Themselves only',
            self::Company => 'Everybody in the company',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Self => 'Self',
            self::Company => 'Company',
        };
    }
}
