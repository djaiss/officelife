<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Who caused an event. A user did it, the application did it on its own (a
 * scheduled check, for instance), or something outside the application reported
 * it.
 */
enum DomainEventActorEnum: string
{
    case User = 'user';
    case System = 'system';
    case Integration = 'integration';
}
