<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Role
 *
 * A named bundle of permissions inside one company. Users are given permissions
 * through roles and in no other way.
 *
 * Owner is not one of these. It is derived from companies.owner_user_id alone,
 * so it can neither be given away nor taken.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $slug
 * @property bool $is_default
 * @property bool $is_editable
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * The slug of the role the first user of a company is given. It is the only
     * default role the application looks up by name, which is why the slug is
     * written down here rather than typed out where it is needed.
     */
    public const string ADMINISTRATOR = 'administrator';

    public const string PEOPLE_ADMINISTRATOR = 'people-administrator';

    public const string MEMBER = 'member';

    public const string IT_ADMINISTRATOR = 'it-administrator';

    protected $table = 'roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'is_default',
        'is_editable',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_editable' => 'boolean',
        ];
    }

    /**
     * Get the company the role belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get what the role is allowed to do, and over whom.
     *
     * @return HasMany<RolePermission, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    /**
     * Get the users who hold the role.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')->withTimestamps();
    }
}
