<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DomainEventActorEnum;
use App\Enums\DomainEventTypeEnum;
use Carbon\Carbon;
use Database\Factories\DomainEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class DomainEvent
 *
 * Something that happened, written down. Every event is persisted before
 * anything reacts to it, which is what separates this from the Laravel event
 * system: it can be read days later, and an event reported by an integration
 * sits in the same table as one raised in here.
 *
 * It is not the log of user actions. That records what somebody did so that they
 * can read it back on their own settings screen. This exists so that playbooks
 * and integrations have something to react to.
 *
 * @property int $id
 * @property int|null $company_id
 * @property DomainEventTypeEnum $type
 * @property string $source
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property DomainEventActorEnum $actor_type
 * @property int|null $actor_id
 * @property array<string, mixed>|null $payload
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 */
class DomainEvent extends Model
{
    /** @use HasFactory<DomainEventFactory> */
    use HasFactory;

    /**
     * The source of an event raised by the application itself, as opposed to one
     * reported by something outside it.
     */
    public const string SOURCE_INTERNAL = 'internal';

    protected $table = 'domain_events';

    /**
     * An event is written once and never changed, so there is nothing to stamp
     * an update with.
     */
    public const null UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'type',
        'source',
        'subject_type',
        'subject_id',
        'actor_type',
        'actor_id',
        'payload',
        'occurred_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DomainEventTypeEnum::class,
            'actor_type' => DomainEventActorEnum::class,
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * Get the company the event happened in.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the thing the event is about, which can be any model at all.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who caused the event, when a user did.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
