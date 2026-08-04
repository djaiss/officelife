<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ManufacturerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Manufacturer
 *
 * Who makes a piece of equipment. Kept apart from who sold it, because the
 * number to ring about a broken screen is not the number to ring about an
 * invoice. A company that buys direct has the same organisation in both places,
 * which costs one row.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $website_url
 * @property string|null $support_url
 * @property string|null $support_email
 * @property string|null $support_phone
 * @property string|null $notes
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Manufacturer extends Model
{
    /** @use HasFactory<ManufacturerFactory> */
    use HasFactory;

    protected $table = 'manufacturers';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'website_url',
        'support_url',
        'support_email',
        'support_phone',
        'notes',
    ];

    /**
     * Get the company the manufacturer belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the models this manufacturer makes.
     *
     * @return HasMany<AssetModel, $this>
     */
    public function assetModels(): HasMany
    {
        return $this->hasMany(AssetModel::class);
    }
}
