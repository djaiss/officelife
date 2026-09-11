<?php

declare(strict_types=1);

namespace App\Enums;

enum OccurrenceActorEnum: string
{
    case User = 'user';
    case System = 'system';
    case Integration = 'integration';
}
