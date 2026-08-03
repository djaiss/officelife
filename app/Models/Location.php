<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class Location
 *
 * An office of a company. The company owns the list, the same way it owns its
 * roles, so an address is written down once and referred to afterwards rather
 * than typed again on every employee who works there.
 *
 * A company that works entirely remotely simply has none.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $country
 * @property string|null $city
 * @property string|null $address
 * @property string|null $timezone
 * @property Carbon|null $archived_at
 * @property bool $is_primary
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    protected $table = 'locations';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'country',
        'city',
        'address',
        'timezone',
        'archived_at',
        'is_primary',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Get the company the office belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the equipment that has been assigned to the office, and the equipment
     * assigned to it now.
     *
     * @return MorphMany<AssetAssignment, $this>
     */
    public function assetAssignments(): MorphMany
    {
        return $this->morphMany(AssetAssignment::class, 'assignee');
    }

    /**
     * Get whether the office has been closed. An archived office keeps its row,
     * so what was written down about it is still there to read.
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}
