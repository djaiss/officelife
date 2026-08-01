<?php

declare(strict_types=1);

namespace App\Permissions;

use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The answer to one permission check, asked in the two ways the application
 * needs it: as something to act on, and as something to show.
 */
class PermissionDecision
{
    public function __construct(
        private readonly bool $allowed,
    ) {}

    /**
     * Get whether the check passed. This is what the interface asks, so it can
     * leave out a control somebody cannot use.
     */
    public function allowed(): bool
    {
        return $this->allowed;
    }

    /**
     * Stop unless the check passed. A refusal is told apart from a missing
     * record nowhere: both come out as a 404, so nobody learns whether what
     * they asked for exists.
     */
    public function authorize(): void
    {
        if ($this->allowed) {
            return;
        }

        throw new ModelNotFoundException('Not found');
    }
}
