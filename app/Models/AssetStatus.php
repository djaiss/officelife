<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssetStatusTypeEnum;
use Carbon\Carbon;
use Database\Factories\AssetStatusFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class AssetStatus
 *
 * What state a piece of equipment is in, which is not the same question as who
 * is holding it. Being handed to somebody is derived from an active assignment
 * and is never stored here.
 *
 * The status splits in two. The type is code, four closed values that checkout
 * branches on. The row is the company's: a label over one of those types, so a
 * company can add Awaiting wipe or In transit and have it behave correctly with
 * nothing written for it.
 *
 * The key is the seam between the two, set only on the handful of system rows
 * the code has to recognise by name. Today that is lost and nothing else, and
 * the list is meant to stay short.
 *
 * @property int $id
 * @property int|null $company_id
 * @property string|null $key
 * @property string $name
 * @property string|null $name_translation_key
 * @property AssetStatusTypeEnum $type
 * @property string|null $color
 * @property bool $is_system
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class AssetStatus extends Model
{
    /** @use HasFactory<AssetStatusFactory> */
    use HasFactory;

    public const string READY_TO_DEPLOY = 'ready_to_deploy';

    public const string PENDING = 'pending';

    public const string AWAITING_REPAIR = 'awaiting_repair';

    /**
     * The one key the code branches on: an asset moving to this status is
     * reported lost. Every key added here is a status the code cares about by
     * name rather than by type, which is worth staying rare.
     */
    public const string LOST = 'lost';

    public const string RETIRED = 'retired';

    protected $table = 'asset_statuses';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'key',
        'name',
        'name_translation_key',
        'type',
        'color',
        'is_system',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AssetStatusTypeEnum::class,
            'is_system' => 'boolean',
        ];
    }

    /**
     * Get the company that added the status, when a company did.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the equipment currently in this state.
     *
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'status_id');
    }

    /**
     * Get what the status is called, which is whatever the company typed, and
     * the translation of the key we shipped it with until they type anything.
     *
     * The five we ship read in the language of whoever is looking. One a company
     * added reads as they named it, in every language, because a name somebody
     * chose is not ours to translate.
     *
     * Writing goes straight to the column. Only reading falls back, so a status
     * still carrying our key stores no name of its own.
     *
     * @return Attribute<string, string|null>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => $value ?? __($this->name_translation_key ?? ''),
            set: fn (?string $value): ?string => $value,
        );
    }

    /**
     * Get whether equipment in this state may be handed to somebody.
     */
    public function isDeployable(): bool
    {
        return $this->type->isDeployable();
    }

    /**
     * Get whether the status means the equipment has gone missing.
     */
    public function meansLost(): bool
    {
        return $this->key === self::LOST;
    }
}
