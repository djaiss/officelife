<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DomainEventActorEnum;
use App\Enums\DomainEventTypeEnum;
use App\Events\DomainEventOccurred;
use App\Models\Company;
use App\Models\DomainEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * The one way business code says that something happened. Nothing calls the
 * Laravel event() helper for a business event, and nothing writes to the
 * domain_events table by hand.
 *
 * Publishing does two things, in this order: write the event down, then dispatch
 * the single internal event carrying it. Writing first is what makes the log a
 * record of what happened rather than a record of what happened to be listened
 * to.
 */
class DomainEvents
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function publish(
        DomainEventTypeEnum $type,
        ?Company $company = null,
        ?Model $subject = null,
        ?User $actor = null,
        array $payload = [],
        ?Carbon $occurredAt = null,
        string $source = DomainEvent::SOURCE_INTERNAL,
    ): DomainEvent {
        $event = DomainEvent::query()->create([
            'company_id' => $company?->id,
            'type' => $type,
            'source' => $source,
            'subject_type' => $subject === null ? null : $subject::class,
            'subject_id' => $subject?->getKey(),
            'actor_type' => $actor === null ? DomainEventActorEnum::System : DomainEventActorEnum::User,
            'actor_id' => $actor?->id,
            'payload' => $payload === [] ? null : $payload,
            'occurred_at' => $occurredAt ?? now(),
        ]);

        DomainEventOccurred::dispatch($event);

        return $event;
    }
}
