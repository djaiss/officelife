<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DomainEventTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use App\Services\DomainEvents;
use InvalidArgumentException;

/**
 * Add an office to a company. The company owns the list, and only somebody who
 * may change its settings may add to it.
 */
class CreateLocation
{
    private Location $location;

    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private string $name,
        private ?string $country = null,
        private ?string $city = null,
        private ?string $address = null,
        private ?string $timezone = null,
    ) {}

    public function execute(): Location
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->publish();
        $this->log();

        return $this->location;
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

        $taken = Location::query()
            ->where('company_id', $this->company->id)
            ->where('name', $this->name)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already has an office called '.$this->name);
        }
    }

    private function create(): void
    {
        $this->location = Location::query()->create([
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
        DomainEvents::publish(
            type: DomainEventTypeEnum::LocationCreated,
            company: $this->company,
            subject: $this->location,
            actor: $this->author,
            payload: ['name' => $this->location->name],
        );
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::LocationCreation,
            parameters: ['name' => $this->location->name],
        )->onQueue('low');
    }
}
