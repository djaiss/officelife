<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;

/**
 * Revoke an API key, which stops working the moment this runs. The key is
 * looked for among the ones this person owns, so asking for somebody else's by
 * its id finds nothing rather than revoking it.
 */
class DestroyApiKey
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly int $apiKeyId,
    ) {}

    public function execute(): void
    {
        $apiKey = $this->user->tokens()->findOrFail($this->apiKeyId);

        $this->name = $apiKey->name;
        $apiKey->delete();

        $this->log();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::ApiKeyDeleted,
            parameters: ['name' => $this->name],
        )->onQueue('low');
    }
}
