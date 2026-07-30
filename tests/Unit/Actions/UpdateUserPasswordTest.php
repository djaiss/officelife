<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateUserPasswordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_password(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('beets')]);

        $result = new UpdateUserPassword(
            user: $user,
            password: 'bearsbeatsbattlestar',
        )->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue(Hash::check('bearsbeatsbattlestar', $user->refresh()->password_hash));
    }

    #[Test]
    public function it_throws_when_the_user_signs_in_through_sso(): void
    {
        $this->expectException(ValidationException::class);

        $user = User::factory()->singleSignOn()->create();

        new UpdateUserPassword(
            user: $user,
            password: 'bearsbeatsbattlestar',
        )->execute();
    }
}
