<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a status behaves. This is the part of a status the code branches on, and
 * it is closed: a company names its own statuses, and each one declares itself
 * as one of these four, which is how a status nobody wrote code for still
 * behaves correctly.
 */
enum AssetStatusTypeEnum: string
{
    case Deployable = 'deployable';
    case Pending = 'pending';
    case Undeployable = 'undeployable';
    case Archived = 'archived';

    /**
     * Whether a piece of equipment in this state may be handed to somebody.
     * Only one of the four says yes, and checkout asks nothing else.
     */
    public function isDeployable(): bool
    {
        return $this === self::Deployable;
    }

    /**
     * What the type is called on the screen where somebody adds a status of
     * their own. The sentence doubles as the translation key.
     */
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
