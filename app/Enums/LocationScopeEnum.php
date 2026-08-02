<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Which offices the locations screen is showing. It is the last segment of the
 * path rather than a query string, so each of the three lists is a page of its
 * own that can be linked to and gone back to.
 */
enum LocationScopeEnum: string
{
    case Active = 'active';
    case Archived = 'archived';
    case All = 'all';

    /**
     * What the segment reads as in the path. The open offices are what the
     * screen shows when nothing is asked for, so they have no segment at all.
     */
    public function segment(): ?string
    {
        return match ($this) {
            self::Active => null,
            self::Archived => 'archived',
            self::All => 'all',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Open',
            self::Archived => 'Archived',
            self::All => 'All',
        };
    }

    /**
     * The scope the segment of the path stands for. Anything else is the list of
     * open offices, which is what the route constraint already guarantees.
     */
    public static function fromSegment(?string $segment): self
    {
        return match ($segment) {
            'archived' => self::Archived,
            'all' => self::All,
            default => self::Active,
        };
    }
}
