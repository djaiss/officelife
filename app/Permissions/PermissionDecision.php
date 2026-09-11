<?php

declare(strict_types=1);

namespace App\Permissions;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class PermissionDecision
{
    public function __construct(
        private readonly bool $allowed,
    ) {}

    public function allowed(): bool
    {
        return $this->allowed;
    }

    public function authorize(): void
    {
        if ($this->allowed) {
            return;
        }

        // A refusal and a missing record answer alike, so nobody learns whether
        // what they asked for exists.
        throw new ModelNotFoundException('Not found');
    }
}
