<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssetAssigneeTypeEnum;
use App\Enums\AssetConditionEnum;
use Carbon\Carbon;
use Database\Factories\AssetAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class AssetAssignment
 *
 * One spell of somebody holding a piece of equipment. The row is never updated
 * to point at somebody else: handing the equipment back closes this one, and
 * handing it out again opens another.
 *
 * A single column on the asset saying who has it would answer one question. This
 * answers four: who has it, who had it before, what state it was in each time,
 * and when it was supposed to come back.
 *
 * @property int $id
 * @property int $asset_id
 * @property AssetAssigneeTypeEnum $assignee_type
 * @property int $assignee_id
 * @property int|null $assigned_by_user_id
 * @property Carbon $assigned_at
 * @property Carbon|null $expected_return_at
 * @property Carbon|null $returned_at
 * @property int|null $returned_to_location_id
 * @property string|null $checkout_notes
 * @property string|null $checkin_notes
 * @property AssetConditionEnum|null $condition_at_checkout
 * @property AssetConditionEnum|null $condition_at_checkin
 * @property Carbon|null $overdue_notified_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class AssetAssignment extends Model
{
    /** @use HasFactory<AssetAssignmentFactory> */
    use HasFactory;

    protected $table = 'asset_assignments';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'asset_id',
        'assignee_type',
        'assignee_id',
        'assigned_by_user_id',
        'assigned_at',
        'expected_return_at',
        'returned_at',
        'returned_to_location_id',
        'checkout_notes',
        'checkin_notes',
        'condition_at_checkout',
        'condition_at_checkin',
        'overdue_notified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assignee_type' => AssetAssigneeTypeEnum::class,
            'assigned_at' => 'datetime',
            'expected_return_at' => 'date',
            'returned_at' => 'datetime',
            'condition_at_checkout' => AssetConditionEnum::class,
            'condition_at_checkin' => AssetConditionEnum::class,
            'overdue_notified_at' => 'datetime',
        ];
    }

    /**
     * Get the equipment that changed hands.
     *
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get who or what is holding the equipment: a colleague, an office, or
     * another piece of equipment.
     *
     * @return MorphTo<Model, $this>
     */
    public function assignee(): MorphTo
    {
        return $this->morphTo(type: 'assignee_type', id: 'assignee_id');
    }

    /**
     * Get who handed the equipment over.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    /**
     * Get the office the equipment came back to.
     *
     * @return BelongsTo<Location, $this>
     */
    public function returnedToLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'returned_to_location_id');
    }

    /**
     * Get whether somebody still has the equipment.
     */
    public function isActive(): bool
    {
        return $this->returned_at === null;
    }

    /**
     * Get whether the equipment was due back before now and has not come back.
     */
    public function isOverdue(): bool
    {
        return $this->isActive()
            && $this->expected_return_at !== null
            && $this->expected_return_at->isPast();
    }
}
