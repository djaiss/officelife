<?php

declare(strict_types=1);

namespace App\Enums;

enum AssetCategoryTypeEnum: string
{
    case Asset = 'asset';
    case Accessory = 'accessory';
    case Consumable = 'consumable';
    case Component = 'component';
    case Licence = 'licence';

    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Equipment tracked one item at a time',
            self::Accessory => 'Accessories counted by quantity',
            self::Consumable => 'Supplies that are handed out and not returned',
            self::Component => 'Parts installed inside an asset',
            self::Licence => 'Software licences with seats',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::Asset;
    }
}
