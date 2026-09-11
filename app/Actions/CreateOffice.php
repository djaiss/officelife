<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OccurrenceTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Office;
use App\Models\User;
use InvalidArgumentException;

/**
 * Add an office to a company. The company owns the list, and only somebody who
 * may change its settings may add to it.
 */
class CreateOffice
{
    private Office $office;

    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private string $name,
        private ?string $country = null,
        private ?string $city = null,
        private ?string $address = null,
        private ?string $timezone = null,
    ) {}

    public function execute(): Office
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->publish();
        $this->log();

        return $this->office;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->company)
            ->authorize();
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->city = TextSanitizer::nullablePlainText($this->city);
        $this->address = TextSanitizer::nullablePlainText($this->address);
        $this->timezone = TextSanitizer::nullablePlainText($this->timezone);

        $country = TextSanitizer::nullablePlainText($this->country);
        $this->country = $country === null ? null : mb_strtoupper($country);
    }

    /**
     * An office has no slug to tell it apart from another, so the name is what
     * everybody reads it by. Two offices of the same company sharing one is a
     * list nobody can use.
     */
    private function validate(): void
    {
        if ($this->name === '') {
            throw new InvalidArgumentException('An office needs a name');
        }

        $taken = Office::query()
            ->where('company_id', $this->company->id)
            ->where('name', $this->name)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already has an office called '.$this->name);
        }
    }

    private function create(): void
    {
        $this->office = Office::query()->create([
            'company_id' => $this->company->id,
            'name' => $this->name,
            'country' => $this->country,
            'city' => $this->city,
            'address' => $this->address,
            'timezone' => $this->timezone,
        ]);
    }

    private function publish(): void
    {
        new PublishOccurrence(
            type: OccurrenceTypeEnum::OfficeCreated,
            company: $this->company,
            subject: $this->office,
            actor: $this->author,
            payload: ['name' => $this->office->name],
        )->execute();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::OfficeCreated,
            parameters: ['name' => $this->office->name],
        )->onQueue('low');
    }
}
