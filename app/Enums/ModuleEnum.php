<?php

declare(strict_types=1);

namespace App\Enums;

enum ModuleEnum: string
{
    case Assets = 'assets';

    public function label(): string
    {
        return match ($this) {
            self::Assets => 'Assets',
        };
    }

    public function note(): string
    {
        return match ($this) {
            self::Assets => 'The assets the company owns, who holds them, and getting them back',
        };
    }
}
