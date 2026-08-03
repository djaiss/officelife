<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Asset
 *
 * One piece of equipment, tracked on its own. It carries two identifiers on
 * purpose: the tag is what the company writes on the label and controls, the
 * serial number is what the manufacturer stamped on it and nobody controls.
 *
 * Who is holding it is not a field here. It is the assignment with no return
 * date, which is the one place that answers it.
 *
 * @property int $id
 * @property int $company_id
 * @property int $asset_model_id
 * @property int $status_id
 * @property string $asset_tag
 * @property string|null $serial_number
 * @property string|null $name
 * @property int|null $default_location_id
 * @property int|null $current_location_id
 * @property Carbon|null $purchase_date
 * @property int|null $purchase_cost
 * @property string|null $order_number
 * @property Carbon|null $warranty_expires_at
 * @property Carbon|null $end_of_life_at
 * @property bool $is_byod
 * @property bool $is_requestable
 * @property string|null $notes
 * @property Carbon|null $archived_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    /**
     * What a piece of equipment somebody is holding reads as, wherever it is
     * shown or counted. It is worked out from the assignment rather than stored,
     * so it can never disagree with who actually has the thing.
     */
    public const string DEPLOYED = 'Deployed';

    protected $table = 'assets';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'asset_model_id',
        'status_id',
        'asset_tag',
        'serial_number',
        'name',
        'default_location_id',
        'current_location_id',
        'purchase_date',
        'purchase_cost',
        'order_number',
        'warranty_expires_at',
        'end_of_life_at',
        'is_byod',
        'is_requestable',
        'notes',
        'archived_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_cost' => 'integer',
            'warranty_expires_at' => 'date',
            'end_of_life_at' => 'date',
            'is_byod' => 'boolean',
            'is_requestable' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Get the company that owns the equipment.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the model this one is an example of.
     *
     * @return BelongsTo<AssetModel, $this>
     */
    public function assetModel(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class);
    }

    /**
     * Get what state the equipment is in.
     *
     * @return BelongsTo<AssetStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(AssetStatus::class, 'status_id');
    }

    /**
     * Get the office the equipment belongs to when nobody has it.
     *
     * @return BelongsTo<Location, $this>
     */
    public function defaultLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'default_location_id');
    }

    /**
     * Get the office the equipment is in now.
     *
     * @return BelongsTo<Location, $this>
     */
    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    /**
     * Get everybody who has held the equipment, and everybody who holds it now.
     *
     * @return HasMany<AssetAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    /**
     * Get the assignment nobody has closed, which is the one saying who has the
     * equipment right now.
     *
     * @return HasOne<AssetAssignment, $this>
     */
    public function activeAssignment(): HasOne
    {
        return $this->hasOne(AssetAssignment::class)->whereNull('returned_at');
    }

    /**
     * Get whether somebody is holding the equipment.
     */
    public function isAssigned(): bool
    {
        return $this->activeAssignment()->exists();
    }

    /**
     * Get whether the equipment has left the fleet.
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Get what the equipment reads as, which is Deployed while somebody holds
     * it and the name of its status the rest of the time.
     *
     * Being held is worked out here rather than stored as a status of its own.
     * A status row saying Deployed would be a second answer to a question the
     * assignment table already answers, and the two can disagree.
     *
     * @return Attribute<string, never>
     */
    protected function displayStatus(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->isAssigned() ? self::DEPLOYED : $this->status->name,
        );
    }
}
