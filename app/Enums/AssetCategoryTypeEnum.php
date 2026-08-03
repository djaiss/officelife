<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Which family of equipment a category groups. The families have life cycles too
 * different to share a data model: one is serialised and comes back, one is
 * counted and does not, one lives inside another asset.
 *
 * Only Asset is usable today. The other four are declared so that the categories
 * of a company do not have to be migrated when the rest arrives.
 */
enum AssetCategoryTypeEnum: string
{
    case Asset = 'asset';
    case Accessory = 'accessory';
    case Consumable = 'consumable';
    case Component = 'component';
    case Licence = 'licence';

    /**
     * What the family is called on the screen where somebody picks it. The
     * sentence doubles as the translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Equipment tracked one item at a time',
            self::Accessory => 'Accessories counted by quantity',
            self::Consumable => 'Supplies that are handed out and not returned',
            self::Component => 'Parts installed inside a piece of equipment',
            self::Licence => 'Software licences with seats',
        };
    }

    /**
     * Whether anything can be recorded against the family yet. A category of a
     * family nothing is built for can be created, so that a company can lay its
     * catalogue out in advance, but no model may point at it.
     */
    public function isAvailable(): bool
    {
        return $this === self::Asset;
    }
}
