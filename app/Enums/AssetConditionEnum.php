<?php

declare(strict_types=1);

namespace App\Enums;

enum AssetConditionEnum: string
{
    case New = 'new';
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';
    case Damaged = 'damaged';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Never used',
            self::Good => 'Good',
            self::Fair => 'Worn but working',
            self::Poor => 'Worn and barely working',
            self::Damaged => 'Damaged',
        };
    }
}
