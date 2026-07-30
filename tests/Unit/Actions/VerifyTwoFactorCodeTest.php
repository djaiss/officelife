<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\VerifyTwoFactorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class VerifyTwoFactorCodeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_accepts_a_valid_code(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create(['two_factor_secret' => $secret]);

        $result = new VerifyTwoFactorCode(
            user: $user,
            code: $google2fa->getCurrentOtp($secret),
        )->execute();

        $this->assertTrue($result);
    }

    #[Test]
    public function it_refuses_an_invalid_code(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => new Google2FA()->generateSecretKey(),
        ]);

        $result = new VerifyTwoFactorCode(
            user: $user,
            code: '000000',
        )->execute();

        $this->assertFalse($result);
    }

    #[Test]
    public function it_refuses_a_code_when_the_user_has_no_secret(): void
    {
        $user = User::factory()->create(['two_factor_secret' => null]);

        $result = new VerifyTwoFactorCode(
            user: $user,
            code: '000000',
        )->execute();

        $this->assertFalse($result);
    }

    #[Test]
    public function it_accepts_a_recovery_code_and_burns_it(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => new Google2FA()->generateSecretKey(),
            'two_factor_recovery_codes' => ['dunder-1111', 'mifflin-2222'],
        ]);

        $result = new VerifyTwoFactorCode(
            user: $user,
            code: 'dunder-1111',
        )->execute();

        $this->assertTrue($result);
        $this->assertEquals(['mifflin-2222'], $user->refresh()->two_factor_recovery_codes);
    }

    #[Test]
    public function it_falls_back_to_the_recovery_codes_when_the_secret_is_unreadable(): void
    {
        // A secret that is not valid base32 makes the library throw rather than
        // answer false, which would otherwise lock the user out of their own
        // recovery codes.
        $user = User::factory()->create([
            'two_factor_secret' => 'not base32 at all',
            'two_factor_recovery_codes' => ['dunder-1111'],
        ]);

        $result = new VerifyTwoFactorCode(
            user: $user,
            code: 'dunder-1111',
        )->execute();

        $this->assertTrue($result);
    }
}
