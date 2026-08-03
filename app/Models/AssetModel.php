<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\AssetModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class AssetModel
 *
 * The kind of thing a piece of equipment is, as opposed to the piece itself.
 * Every asset belongs to one, and the model carries what forty identical
 * laptops have in common, so the manufacturer is written down once rather than
 * forty times.
 *
 * @property int $id
 * @property int $company_id
 * @property int $manufacturer_id
 * @property int $asset_category_id
 * @property string $name
 * @property string|null $model_number
 * @property string|null $image_path
 * @property int|null $useful_life_months
 * @property bool $is_requestable
 * @property string|null $notes
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class AssetModel extends Model
{
    /** @use HasFactory<AssetModelFactory> */
    use HasFactory;

    protected $table = 'asset_models';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'manufacturer_id',
        'asset_category_id',
        'name',
        'model_number',
        'image_path',
        'useful_life_months',
        'is_requestable',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'useful_life_months' => 'integer',
            'is_requestable' => 'boolean',
        ];
    }

    /**
     * Get the company the model belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get who makes it.
     *
     * @return BelongsTo<Manufacturer, $this>
     */
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * Get the family it belongs to.
     *
     * @return BelongsTo<AssetCategory, $this>
     */
    public function assetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class);
    }

    /**
     * Get every piece of equipment of this model the company owns.
     *
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
