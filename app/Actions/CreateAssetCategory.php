<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AssetCategoryTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\AssetCategory;
use App\Models\Company;
use App\Models\User;
use InvalidArgumentException;

/**
 * Add a category to the catalogue of a company. The category is where the rules
 * that apply to a whole family of equipment live.
 */
class CreateAssetCategory
{
    private AssetCategory $category;

    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private string $name,
        private readonly AssetCategoryTypeEnum $type = AssetCategoryTypeEnum::Asset,
        private readonly bool $requiresAcceptance = false,
        private ?string $eulaText = null,
        private readonly bool $sendCheckoutEmail = false,
    ) {}

    public function execute(): AssetCategory
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->category;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::AssetManage)
            ->forCompany($this->company)
            ->authorize();
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->eulaText = TextSanitizer::nullablePlainText($this->eulaText);
    }

    /**
     * A category that asks people to accept terms without saying what they are
     * would put an empty page in front of somebody and ask them to agree to it.
     */
    private function validate(): void
    {
        if ($this->name === '') {
            throw new InvalidArgumentException('A category needs a name');
        }

        if ($this->requiresAcceptance && ($this->eulaText === null || $this->eulaText === '')) {
            throw new InvalidArgumentException('A category that asks for acceptance needs terms to accept');
        }

        $taken = AssetCategory::query()
            ->where('company_id', $this->company->id)
            ->where('name', $this->name)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already has a category called '.$this->name);
        }
    }

    private function create(): void
    {
        $this->category = AssetCategory::query()->create([
            'company_id' => $this->company->id,
            'name' => $this->name,
            'type' => $this->type,
            'requires_acceptance' => $this->requiresAcceptance,
            'eula_text' => $this->eulaText,
            'send_checkout_email' => $this->sendCheckoutEmail,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::AssetCategoryCreation,
            parameters: ['name' => $this->category->name],
        )->onQueue('low');
    }
}
