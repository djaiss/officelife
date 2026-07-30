<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Change the password of a user. A user who signs in through SSO has no
 * password to change.
 */
class UpdateUserPassword
{
    public function __construct(
        private readonly User $user,
        private readonly string $password,
    ) {}

    public function execute(): User
    {
        $this->validate();
        $this->update();

        return $this->user;
    }

    private function validate(): void
    {
        if ($this->user->usesSingleSignOn()) {
            throw ValidationException::withMessages([
                'password' => 'This user signs in through SSO and has no password.',
            ]);
        }
    }

    private function update(): void
    {
        $this->user->password_hash = Hash::make($this->password);
        $this->user->save();
    }
}
