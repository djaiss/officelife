<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Administration;

use App\Enums\OfficeScopeEnum;
use App\Models\Employee;
use App\Models\Office;
use App\Models\User;
use DateTimeZone;
use Illuminate\Database\Eloquent\Collection;

class OfficesViewModel
{
    /** @var Collection<int, Office>|null */
    private ?Collection $offices = null;

    public function __construct(
        private readonly User $user,
        private readonly ?Employee $employee,
        private readonly OfficeScopeEnum $scope,
        private readonly string $search = '',
        private readonly string $sort = 'name',
    ) {}

    public function companyName(): string
    {
        return $this->user->company->name;
    }

    public function name(): string
    {
        return $this->employee->name ?? $this->user->email;
    }

    public function employee(): ?Employee
    {
        return $this->employee;
    }

    /** @return array<int, array{value: int, label: string, icon: string, hue: int}> */
    public function stats(): array
    {
        $open = $this->offices()->reject(fn (Office $office): bool => $office->isArchived());

        return [
            [
                'value' => $open->count(),
                'label' => __('open offices'),
                'icon' => 'offices',
                'hue' => 80,
            ],
            [
                'value' => $open->pluck('country')->filter()->unique()->count(),
                'label' => __('countries'),
                'icon' => 'countries',
                'hue' => 150,
            ],
            [
                'value' => $open->pluck('timezone')->filter()->unique()->count(),
                'label' => __('time zones'),
                'icon' => 'time-zones',
                'hue' => 250,
            ],
        ];
    }

    /** @return array<int, array{label: string, url: string, current: bool}> */
    public function scopes(): array
    {
        return array_map(fn (OfficeScopeEnum $scope): array => [
            'label' => __($scope->label()),
            'url' => $this->url($scope),
            'current' => $scope === $this->scope,
        ], OfficeScopeEnum::cases());
    }

    /** @return array<int, array{id: int, name: string, badge: string, place: string, timezone: string, isHeadOffice: bool, isArchived: bool}> */
    public function rows(): array
    {
        return array_values(array_map(fn (Office $office): array => [
            'id' => $office->id,
            'name' => $office->name,
            'badge' => $this->badge($office),
            'place' => $this->place($office),
            'timezone' => $office->timezone ?? __('same as the company'),
            'isHeadOffice' => $office->is_head_office,
            'isArchived' => $office->isArchived(),
        ], $this->filtered()->all()));
    }

    /** @return array<int, array{id: int, name: string, country: string, city: string, address: string, timezone: string, isHeadOffice: bool, isArchived: bool, updateUrl: string, archiveUrl: string, restoreUrl: string, inheritNote: string}> */
    public function drawer(): array
    {
        $drawer = [];

        foreach ($this->filtered() as $office) {
            $drawer[$office->id] = [
                'id' => $office->id,
                'name' => $office->name,
                'country' => $office->country ?? '',
                'city' => $office->city ?? '',
                'address' => $office->address ?? '',
                'timezone' => $office->timezone ?? '',
                'isHeadOffice' => $office->is_head_office,
                'isArchived' => $office->isArchived(),
                'updateUrl' => route('settings.offices.update', $office->id),
                'archiveUrl' => route('settings.officeArchives.create', $office->id),
                'restoreUrl' => route('settings.officeArchives.destroy', $office->id),
                'inheritNote' => $this->inheritNote($office),
            ];
        }

        return $drawer;
    }

    public function openOfficeId(): ?int
    {
        $id = old('office_id');

        return $id === null ? null : (int) $id;
    }

    /** @return array{name: string, country: string, city: string, address: string, timezone: string, isHeadOffice: bool} */
    public function openOfficeForm(): array
    {
        return [
            'name' => (string) old('name', ''),
            'country' => (string) old('country', ''),
            'city' => (string) old('city', ''),
            'address' => (string) old('address', ''),
            'timezone' => (string) old('timezone', ''),
            'isHeadOffice' => old('is_head_office') !== null,
        ];
    }

    /** @return array<int, string> */
    public function timezones(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    public function search(): string
    {
        return $this->search;
    }

    /** @return array<string, string> */
    public function sortState(): array
    {
        return ['sort' => $this->sort];
    }

    /** @return array{label: string, description: string, url: string} */
    public function sortToggle(): array
    {
        $byName = $this->sort === 'name';

        return [
            'label' => $byName ? __('Sorted by office') : __('Sorted by city'),
            'description' => $byName
                ? __('Sorted by office. Sort by city instead.')
                : __('Sorted by city. Sort by office instead.'),
            'url' => $this->url($this->scope, ['sort' => $byName ? 'place' : '']),
        ];
    }

    public function createUrl(): string
    {
        return route('settings.offices.create');
    }

    public function companyHasNoOffice(): bool
    {
        return $this->offices()->isEmpty();
    }

    private function badge(Office $office): string
    {
        if ($office->isArchived()) {
            return __('Archived');
        }

        return $office->is_head_office ? __('Head office') : '';
    }

    private function place(Office $office): string
    {
        $parts = array_filter([$office->city, $office->country]);

        return $parts === [] ? __('somewhere unrecorded') : implode(', ', $parts);
    }

    private function inheritNote(Office $office): string
    {
        if ($office->country === null && $office->timezone === null) {
            return __('Fill in the country and the time zone, and the people who work here inherit both.');
        }

        return __('Somebody working here inherits :country and :timezone. Both stay changeable on their own record.', [
            'country' => $office->country ?? __('no country'),
            'timezone' => $office->timezone ?? __('no time zone'),
        ]);
    }

    /** @return Collection<int, Office> */
    private function filtered(): Collection
    {
        $offices = $this->offices()->filter(fn (Office $office): bool => match ($this->scope) {
            OfficeScopeEnum::Active => ! $office->isArchived(),
            OfficeScopeEnum::Archived => $office->isArchived(),
            OfficeScopeEnum::All => true,
        });

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);

            $offices = $offices->filter(
                fn (Office $office): bool => str_contains(
                    mb_strtolower($office->name.' '.$office->city.' '.$office->country),
                    $needle,
                ),
            );
        }

        return $offices->sortBy(
            fn (Office $office): string => $this->sort === 'place'
                ? mb_strtolower($this->place($office))
                : mb_strtolower($office->name),
        );
    }

    /** @param  array<string, string>  $overrides */
    private function url(OfficeScopeEnum $scope, array $overrides = []): string
    {
        $query = array_filter([
            'q' => $this->search,
            'sort' => $this->sort === 'name' ? '' : $this->sort,
            ...$overrides,
        ]);

        return route('settings.offices.index', array_filter([
            'scope' => $scope->segment(),
            ...$query,
        ]));
    }

    /** @return Collection<int, Office> */
    private function offices(): Collection
    {
        return $this->offices ??= $this->user->company->offices()->get();
    }
}
