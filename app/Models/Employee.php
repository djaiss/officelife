<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Employee
 *
 * Somebody who works for a company. An employee does not necessarily have an
 * account, and a user does not necessarily have an employee record.
 *
 * @property int $id
 * @property int $company_id
 * @property string $first_name
 * @property string $last_name
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    protected $table = 'employees';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
    ];

    /**
     * Get the company the employee works for.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the account of the employee, when they have one.
     *
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Get the full name of the employee.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->first_name.' '.$this->last_name,
        );
    }
}
