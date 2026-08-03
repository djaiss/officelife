<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\AssetCategory;
use App\Models\User;
use InvalidArgumentException;

/**
 * Change a category of the catalogue. The family it groups cannot be changed:
 * a category full of laptops is not turned into a category of consumables
 * without every asset filed under it becoming nonsense.
 */
class UpdateAssetCategory
{
    public function __construct(
        private readonly User $author,
        private readonly AssetCategory $category,
        private string $name,
        private readonly bool $requiresAcceptance = false,
        private ?string $eulaText = null,
        private readonly bool $sendCheckoutEmail = false,
    ) {}

    public function execute(): AssetCategory
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->category;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::AssetManage)
            ->forCompany($this->category->company)
            ->authorize();
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->eulaText = TextSanitizer::nullablePlainText($this->eulaText);
    }

    private function validate(): void
    {
        if ($this->name === '') {
            throw new InvalidArgumentException('A category needs a name');
        }

        if ($this->requiresAcceptance && ($this->eulaText === null || $this->eulaText === '')) {
            throw new InvalidArgumentException('A category that asks for acceptance needs terms to accept');
        }

        $taken = AssetCategory::query()
            ->where('company_id', $this->category->company_id)
            ->where('name', $this->name)
            ->whereKeyNot($this->category->id)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already has a category called '.$this->name);
        }
    }

    private function update(): void
    {
        $this->category->name = $this->name;
        $this->category->requires_acceptance = $this->requiresAcceptance;
        $this->category->eula_text = $this->eulaText;
        $this->category->send_checkout_email = $this->sendCheckoutEmail;
        $this->category->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->category->company,
            user: $this->author,
            action: UserActionEnum::AssetCategoryUpdate,
            parameters: ['name' => $this->category->name],
        )->onQueue('low');
    }
}
