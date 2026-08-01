<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\SizeRangeEnum;
use App\Enums\UserActionEnum;
use App\Enums\WorkModeEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;

/**
 * Update the information of a company. Only somebody who may look after the
 * settings of the company can do this.
 */
class UpdateCompany
{
    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private string $name,
        private ?string $legalName = null,
        private ?string $websiteUrl = null,
        private ?string $industry = null,
        private readonly ?SizeRangeEnum $sizeRange = null,
        private readonly ?WorkModeEnum $workMode = null,
        private readonly string $timezone = 'UTC',
        private readonly string $locale = 'en',
        private readonly ?string $currency = null,
        private ?string $billingEmail = null,
    ) {}

    public function execute(): Company
    {
        $this->authorize();
        $this->sanitize();
        $this->update();
        $this->log();

        return $this->company;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->company)
            ->authorize();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::CompanyUpdate,
            parameters: ['name' => $this->name],
        )->onQueue('low');
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->legalName = TextSanitizer::nullablePlainText($this->legalName);
        $this->websiteUrl = TextSanitizer::nullablePlainText($this->websiteUrl);
        $this->industry = TextSanitizer::nullablePlainText($this->industry);
        $this->billingEmail = TextSanitizer::nullablePlainText($this->billingEmail);
    }

    private function update(): void
    {
        $this->company->name = $this->name;
        $this->company->legal_name = $this->legalName;
        $this->company->website_url = $this->websiteUrl;
        $this->company->industry = $this->industry;
        $this->company->size_range = $this->sizeRange;
        $this->company->work_mode = $this->workMode;
        $this->company->timezone = $this->timezone;
        $this->company->locale = $this->locale;
        $this->company->currency = $this->currency;
        $this->company->billing_email = $this->billingEmail;
        $this->company->save();
    }
}
