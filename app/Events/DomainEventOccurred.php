<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\DomainEvent;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The one internal event the application dispatches, whatever the business event
 * that was published. Nothing subscribes to a business type directly.
 *
 * That is deliberate. One publisher, one internal event, one listener is what
 * keeps the log a complete record of everything that could have reacted. A
 * second subscriber somewhere else, listening to something the publisher never
 * wrote down, and the guarantee is gone.
 */
class DomainEventOccurred
{
    use Dispatchable;

    public function __construct(
        public readonly DomainEvent $domainEvent,
    ) {}
}
