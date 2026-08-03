<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Asset;
use App\Models\Employee;
use App\Models\Location;

/**
 * What kind of thing can be holding a piece of equipment. A display assigned to
 * a meeting room, a docking station assigned to a laptop and a laptop assigned
 * to a person are the same operation against different targets.
 */
enum AssetAssigneeTypeEnum: string
{
    case Employee = 'employee';
    case Location = 'location';
    case Asset = 'asset';

    /**
     * The model the type stands for.
     *
     * @return class-string
     */
    public function model(): string
    {
        return match ($this) {
            self::Employee => Employee::class,
            self::Location => Location::class,
            self::Asset => Asset::class,
        };
    }

    /**
     * The type standing for a model, or null when nothing can be assigned to it.
     */
    public static function forModel(object $assignee): ?self
    {
        return match ($assignee::class) {
            Employee::class => self::Employee,
            Location::class => self::Location,
            Asset::class => self::Asset,
            default => null,
        };
    }
}
