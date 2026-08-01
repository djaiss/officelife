<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\UserRoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class UserRole
 *
 * One role held by one person, and the day they were given it.
 *
 * Roles are handed out and taken back through the relation on the two models it
 * joins, which is what AssignRole and RemoveRole use. This is here for the one
 * thing the relation cannot say: when the row itself was written.
 *
 * @property int $id
 * @property int $user_id
 * @property int $role_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class UserRole extends Model
{
    /** @use HasFactory<UserRoleFactory> */
    use HasFactory;

    protected $table = 'user_roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'role_id',
    ];

    /**
     * Get the person who holds the role.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role they hold.
     *
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
