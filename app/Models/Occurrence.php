<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OccurrenceActorEnum;
use App\Enums\OccurrenceTypeEnum;
use Carbon\Carbon;
use Database\Factories\OccurrenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Occurrence
 *
 * One thing that happened in a company, written down at the moment it happened.
 * Somebody arrived, a laptop was handed over, an office was closed, an issue was
 * opened on GitHub. Each of those is an occurrence.
 *
 * Four things define it.
 *
 * It is a fact, not an intention. An occurrence says a thing happened. It never
 * says what should be done about it, and it is never changed or deleted
 * afterwards, because the past does not change.
 *
 * It is written down before anything reacts to it. That is what separates this
 * from the Laravel event system, where an event exists only for as long as it
 * takes to handle. An occurrence can be read days later, whether or not anything
 * was listening at the time.
 *
 * It does not care what caused it. A person, the schedule, or a tool outside the
 * application: all three write the same kind of row, so an issue opened on
 * GitHub sits beside an employee arriving and a playbook can react to either.
 *
 * It is what playbooks will trigger on. That is the whole reason it exists.
 * Until playbooks are built, nothing reads this table, and that is the intended
 * state rather than an oversight.
 *
 * It is not the log of user actions. That records what somebody did so that
 * person can read it back on their own settings screen: it is a feature, with a
 * reader and a screen. This is infrastructure, read by the application rather
 * than by anybody.
 *
 * @property int $id
 * @property int|null $company_id
 * @property OccurrenceTypeEnum $type
 * @property string $source
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property OccurrenceActorEnum $actor_type
 * @property int|null $actor_id
 * @property array<string, mixed>|null $payload
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 */
class Occurrence extends Model
{
    /** @use HasFactory<OccurrenceFactory> */
    use HasFactory;

    /**
     * The source of an event raised by the application itself, as opposed to one
     * reported by something outside it.
     */
    public const string SOURCE_INTERNAL = 'internal';

    protected $table = 'occurrences';

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
            'type' => OccurrenceTypeEnum::class,
            'actor_type' => OccurrenceActorEnum::class,
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
