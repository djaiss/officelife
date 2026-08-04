<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What state a piece of equipment was in when it changed hands. A closed list
 * rather than free text, because "what did it come back like" has to be
 * answerable across a fleet rather than one item at a time.
 */
enum AssetConditionEnum: string
{
    case New = 'new';
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';
    case Damaged = 'damaged';

    /**
     * What the condition is called on the screen where somebody records it. The
     * sentence doubles as the translation key.
     */
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
