<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Asset;
use App\Models\Employee;
use App\Models\Office;

enum AssetAssigneeTypeEnum: string
{
    case Employee = 'employee';
    case Office = 'office';
    case Asset = 'asset';

    /** @return class-string */
    public function model(): string
    {
        return match ($this) {
            self::Employee => Employee::class,
            self::Office => Office::class,
            self::Asset => Asset::class,
        };
    }

    public static function forModel(object $assignee): ?self
    {
        return match ($assignee::class) {
            Employee::class => self::Employee,
            Office::class => self::Office,
            Asset::class => self::Asset,
            default => null,
        };
    }
}
