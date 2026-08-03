<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Manufacturer;
use App\Models\User;
use InvalidArgumentException;

/**
 * Change a manufacturer of the catalogue.
 */
class UpdateManufacturer
{
    public function __construct(
        private readonly User $author,
        private readonly Manufacturer $manufacturer,
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
        $this->update();
        $this->log();

        return $this->manufacturer;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::AssetManage)
            ->forCompany($this->manufacturer->company)
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
            ->where('company_id', $this->manufacturer->company_id)
            ->where('name', $this->name)
            ->whereKeyNot($this->manufacturer->id)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already knows a manufacturer called '.$this->name);
        }
    }

    private function update(): void
    {
        $this->manufacturer->name = $this->name;
        $this->manufacturer->website_url = $this->websiteUrl;
        $this->manufacturer->support_url = $this->supportUrl;
        $this->manufacturer->support_email = $this->supportEmail;
        $this->manufacturer->support_phone = $this->supportPhone;
        $this->manufacturer->notes = $this->notes;
        $this->manufacturer->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->manufacturer->company,
            user: $this->author,
            action: UserActionEnum::ManufacturerUpdate,
            parameters: ['name' => $this->manufacturer->name],
        )->onQueue('low');
    }
}
