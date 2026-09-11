<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Administration;

use App\Enums\OfficeScopeEnum;
use App\Models\Employee;
use App\Models\Office;
use App\Models\User;
use DateTimeZone;
use Illuminate\Database\Eloquent\Collection;

/**
 * What the offices screen shows: the offices of the company as a list, with
 * the three counts above it, and everything the side panel needs to edit one of
 * them without asking the server for it.
 *
 * The scope is part of the path, so it says which of the three lists is being
 * read. The search and the order refine that list rather than name a different
 * one, so they stay in the query string and every link the screen draws carries
 * them along.
 */
class OfficesViewModel
{
    /**
     * The offices of the company, asked for once and kept. The counts, the list
     * and the panel all read them, and each further ask would be another query.
     *
     * @var Collection<int, Office>|null
     */
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

    /**
     * The name to show and to draw initials from. Somebody whose account is not
     * attached to an employee record has only an email address to go by.
     */
    public function name(): string
    {
        return $this->employee->name ?? $this->user->email;
    }

    /**
     * The record the avatar draws from, so the top bar can show it when
     * there is one. An account that belongs to nobody who works here has none.
     */
    public function employee(): ?Employee
    {
        return $this->employee;
    }

    /**
     * The counts above the list. They describe the company rather than the list
     * below them, so narrowing the search does not move them.
     *
     * @return array<int, array{value: int, label: string, icon: string, hue: int}>
     */
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

    /**
     * The three lists on offer, each a path of its own.
     *
     * @return array<int, array{label: string, url: string, current: bool}>
     */
    public function scopes(): array
    {
        return array_map(fn (OfficeScopeEnum $scope): array => [
            'label' => __($scope->label()),
            'url' => $this->url($scope),
            'current' => $scope === $this->scope,
        ], OfficeScopeEnum::cases());
    }

    /**
     * One row of the list. The two letter country code is what the row is
     * searched by rather than shown, and the badge is the one thing worth
     * saying about an office beside its name.
     *
     * @return array<int, array{id: int, name: string, badge: string, place: string, timezone: string, isHeadOffice: bool, isArchived: bool}>
     */
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

    /**
     * Everything the side panel needs to edit an office, keyed by which office it
     * is. It goes into the page as one blob, so opening the panel is a click
     * rather than a page of its own.
     *
     * @return array<int, array{id: int, name: string, country: string, city: string, address: string, timezone: string, isHeadOffice: bool, isArchived: bool, updateUrl: string, archiveUrl: string, restoreUrl: string, inheritNote: string}>
     */
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

    /**
     * The office the panel should open on when the page is drawn. Nothing,
     * usually, and the one a failed save came back from otherwise, so the edit
     * is still there to correct rather than lost behind a closed panel.
     */
    public function openOfficeId(): ?int
    {
        $id = old('office_id');

        return $id === null ? null : (int) $id;
    }

    /**
     * What the fields of the panel hold when the page is drawn. Empty, usually,
     * since the panel fills them from the office that was clicked, and whatever
     * was typed when a save came back rejected otherwise.
     *
     * @return array{name: string, country: string, city: string, address: string, timezone: string, isHeadOffice: bool}
     */
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

    /**
     * Every time zone the world keeps, for the picker in the panel.
     *
     * @return array<int, string>
     */
    public function timezones(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    public function search(): string
    {
        return $this->search;
    }

    /**
     * What the search form has to carry along so that running a search does not
     * quietly put the list back in the order it starts in.
     *
     * @return array<string, string>
     */
    public function sortState(): array
    {
        return ['sort' => $this->sort];
    }

    /**
     * The one control that decides the order of the list. It says which order
     * the list is in and leads to the other one, which is why it carries the
     * whole sentence as well as the two words shown on it.
     *
     * @return array{label: string, description: string, url: string}
     */
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

    /**
     * The one thing worth saying about an office beside its name. An archived
     * office says so rather than saying it is the head office, since nobody can
     * be attached to it either way.
     */
    private function badge(Office $office): string
    {
        if ($office->isArchived()) {
            return __('Archived');
        }

        return $office->is_head_office ? __('Head office') : '';
    }

    /**
     * The city and the country of an office, as one line, skipping whichever of
     * the two was never written down.
     */
    private function place(Office $office): string
    {
        $parts = array_filter([$office->city, $office->country]);

        return $parts === [] ? __('somewhere unrecorded') : implode(', ', $parts);
    }

    /**
     * The line under the fields of the panel, saying what an office hands down to
     * whoever works there.
     */
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

    /**
     * The offices the list shows: the ones the scope asks for, narrowed by the
     * search, in the order the toggle above the list is set to.
     *
     * @return Collection<int, Office>
     */
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

    /**
     * A link to one of the three lists, keeping whatever the current one was
     * narrowed and ordered by so switching scope does not throw a search away.
     *
     * @param  array<string, string>  $overrides
     */
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

    /**
     * @return Collection<int, Office>
     */
    private function offices(): Collection
    {
        return $this->offices ??= $this->user->company->offices()->get();
    }
}
