<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Office;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Change an office of a company. What is passed in replaces what the office
 * had, so a field left out is a field emptied.
 */
class UpdateOffice
{
    public function __construct(
        private readonly User $author,
        private readonly Office $office,
        private string $name,
        private ?string $country = null,
        private ?string $city = null,
        private ?string $address = null,
        private ?string $timezone = null,
        private readonly bool $isHeadOffice = false,
    ) {}

    public function execute(): Office
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->office;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->office->company)
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

    private function validate(): void
    {
        if ($this->name === '') {
            throw new InvalidArgumentException('An office needs a name');
        }

        $taken = Office::query()
            ->where('company_id', $this->office->company_id)
            ->where('name', $this->name)
            ->whereKeyNot($this->office->id)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already has an office called '.$this->name);
        }

        if ($this->isHeadOffice && $this->office->isArchived()) {
            throw new InvalidArgumentException('A closed office cannot be the head office');
        }
    }

    private function update(): void
    {
        DB::transaction(function (): void {
            if ($this->isHeadOffice) {
                Office::query()
                    ->where('company_id', $this->office->company_id)
                    ->whereKeyNot($this->office->id)
                    ->update(['is_head_office' => false]);

                $this->office->is_head_office = true;
            }

            $this->office->name = $this->name;
            $this->office->country = $this->country;
            $this->office->city = $this->city;
            $this->office->address = $this->address;
            $this->office->timezone = $this->timezone;
            $this->office->save();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->office->company,
            user: $this->author,
            action: UserActionEnum::OfficeUpdated,
            parameters: ['name' => $this->office->name],
        )->onQueue('low');
    }
}
