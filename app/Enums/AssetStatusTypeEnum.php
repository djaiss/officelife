<?php

declare(strict_types=1);

namespace App\Enums;

enum AssetStatusTypeEnum: string
{
    case Deployable = 'deployable';
    case Pending = 'pending';
    case Undeployable = 'undeployable';
    case Archived = 'archived';

    public function isDeployable(): bool
    {
        return $this === self::Deployable;
    }

    public function label(): string
    {
        return match ($this) {
            self::Deployable => 'Ready to be handed out',
            self::Pending => 'Not ready yet',
            self::Undeployable => 'Cannot be handed out',
            self::Archived => 'Out of the fleet for good',
        };
    }
}
