<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\TextSanitizer;
use App\Models\User;
use PragmaRX\Google2FA\Exceptions\Google2FAException;
use PragmaRX\Google2FA\Google2FA;

/**
 * Verify the two factor code of a user, either a TOTP code or a recovery code.
 * A recovery code is burnt once it has been used.
 */
readonly class VerifyTwoFactorCode
{
    public function __construct(
        private User $user,
        private string $code,
    ) {}

    /**
     * Execute the verification of the 2FA code.
     *
     * @return bool True if the code is valid, false otherwise
     */
    public function execute(): bool
    {
        if ($this->verifyTotp()) {
            return true;
        }

        return $this->verifyRecoveryCode();
    }

    private function verifyTotp(): bool
    {
        if (! $this->user->two_factor_secret) {
            return false;
        }

        $google2fa = new Google2FA;

        // A secret that is not readable base32, and a code that is not readable
        // at all, both make the library throw. Neither is a reason to answer
        // with a server error, and neither should stop a recovery code from
        // being tried next.
        try {
            return (bool) $google2fa->verifyKey($this->user->two_factor_secret, TextSanitizer::plainText($this->code));
        } catch (Google2FAException) {
            return false;
        }
    }

    private function verifyRecoveryCode(): bool
    {
        if (! is_array($this->user->two_factor_recovery_codes)) {
            return false;
        }

        $codes = $this->user->two_factor_recovery_codes;
        $sanitizedCode = TextSanitizer::plainText($this->code);

        if (in_array($sanitizedCode, $codes, true)) {
            $this->user->two_factor_recovery_codes = array_values(array_diff($codes, [$sanitizedCode]));
            $this->user->save();

            return true;
        }

        return false;
    }
}
