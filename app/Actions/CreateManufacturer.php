<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Manufacturer;
use App\Models\User;
use InvalidArgumentException;

/**
 * Add a manufacturer to the catalogue of a company. Who makes a piece of
 * equipment, as opposed to who sold it.
 */
class CreateManufacturer
{
    private Manufacturer $manufacturer;

    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private string $name,
        private ?string $websiteUrl = null,
        private ?string $supportUrl = null,
        private ?string $supportEmail = null,
        private ?string $supportPhone = null,
        private ?string $notes = null,
    ) {}

    public function execute(): Manufacturer
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->manufacturer;
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
        $this->websiteUrl = TextSanitizer::nullablePlainText($this->websiteUrl);
        $this->supportUrl = TextSanitizer::nullablePlainText($this->supportUrl);
        $this->supportEmail = TextSanitizer::nullablePlainText($this->supportEmail);
        $this->supportPhone = TextSanitizer::nullablePlainText($this->supportPhone);
        $this->notes = TextSanitizer::nullablePlainText($this->notes);
    }

    private function validate(): void
    {
        if ($this->name === '') {
            throw new InvalidArgumentException('A manufacturer needs a name');
        }

        $taken = Manufacturer::query()
            ->where('company_id', $this->company->id)
            ->where('name', $this->name)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already knows a manufacturer called '.$this->name);
        }
    }

    private function create(): void
    {
        $this->manufacturer = Manufacturer::query()->create([
            'company_id' => $this->company->id,
            'name' => $this->name,
            'website_url' => $this->websiteUrl,
            'support_url' => $this->supportUrl,
            'support_email' => $this->supportEmail,
            'support_phone' => $this->supportPhone,
            'notes' => $this->notes,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::ManufacturerCreation,
            parameters: ['name' => $this->manufacturer->name],
        )->onQueue('low');
    }
}
