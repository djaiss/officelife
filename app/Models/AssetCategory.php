<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssetCategoryTypeEnum;
use Carbon\Carbon;
use Database\Factories\AssetCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class AssetCategory
 *
 * A family of equipment, such as laptops or security badges. The category is
 * where the rules that apply to everything in it live: whether the person
 * handed one has to accept terms, and what those terms say.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property AssetCategoryTypeEnum $type
 * @property bool $requires_acceptance
 * @property string|null $eula_text
 * @property bool $send_checkout_email
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class AssetCategory extends Model
{
    /** @use HasFactory<AssetCategoryFactory> */
    use HasFactory;

    protected $table = 'asset_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'requires_acceptance',
        'eula_text',
        'send_checkout_email',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AssetCategoryTypeEnum::class,
            'requires_acceptance' => 'boolean',
            'send_checkout_email' => 'boolean',
        ];
    }

    /**
     * Get the company the category belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the models filed under the category.
     *
     * @return HasMany<AssetModel, $this>
     */
    public function assetModels(): HasMany
    {
        return $this->hasMany(AssetModel::class);
    }
}
