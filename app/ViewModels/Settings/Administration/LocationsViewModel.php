<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Administration;

use App\Enums\LocationScopeEnum;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use DateTimeZone;
use Illuminate\Database\Eloquent\Collection;

/**
 * What the locations screen shows: the offices of the company as a list, with
 * the three counts above it, and everything the side panel needs to edit one of
 * them without asking the server for it.
 *
 * The scope is part of the path, so it says which of the three lists is being
 * read. The search and the order refine that list rather than name a different
 * one, so they stay in the query string and every link the screen draws carries
 * them along.
 */
class LocationsViewModel
{
    /**
     * The offices of the company, asked for once and kept. The counts, the list
     * and the panel all read them, and each further ask would be another query.
     *
     * @var Collection<int, Location>|null
     */
    private ?Collection $locations = null;

    public function __construct(
        private readonly User $user,
        private readonly ?Employee $employee,
        private readonly LocationScopeEnum $scope,
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
     * The record the avatar draws from, so the top bar can show the photo when
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
        $open = $this->locations()->reject(fn (Location $location): bool => $location->isArchived());

        return [
            [
                'value' => $open->count(),
                'label' => __('open offices'),
                'icon' => 'locations',
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
        return array_map(fn (LocationScopeEnum $scope): array => [
            'label' => __($scope->label()),
            'url' => $this->url($scope),
            'current' => $scope === $this->scope,
        ], LocationScopeEnum::cases());
    }

    /**
     * One row of the list. The two letter country code is what the row is
     * searched by rather than shown, and the badge is the one thing worth
     * saying about an office beside its name.
     *
     * @return array<int, array{id: int, name: string, badge: string, place: string, timezone: string, isPrimary: bool, isArchived: bool}>
     */
    public function rows(): array
    {
        return array_values(array_map(fn (Location $location): array => [
            'id' => $location->id,
            'name' => $location->name,
            'badge' => $this->badge($location),
            'place' => $this->place($location),
            'timezone' => $location->timezone ?? __('same as the company'),
            'isPrimary' => $location->is_primary,
            'isArchived' => $location->isArchived(),
        ], $this->filtered()->all()));
    }

    /**
     * Everything the side panel needs to edit an office, keyed by which office it
     * is. It goes into the page as one blob, so opening the panel is a click
     * rather than a page of its own.
     *
     * @return array<int, array{id: int, name: string, country: string, city: string, address: string, timezone: string, isPrimary: bool, isArchived: bool, updateUrl: string, archiveUrl: string, restoreUrl: string, inheritNote: string}>
     */
    public function drawer(): array
    {
        $drawer = [];

        foreach ($this->filtered() as $location) {
            $drawer[$location->id] = [
                'id' => $location->id,
                'name' => $location->name,
                'country' => $location->country ?? '',
                'city' => $location->city ?? '',
                'address' => $location->address ?? '',
                'timezone' => $location->timezone ?? '',
                'isPrimary' => $location->is_primary,
                'isArchived' => $location->isArchived(),
                'updateUrl' => route('settings.locations.update', $location->id),
                'archiveUrl' => route('settings.locationArchives.create', $location->id),
                'restoreUrl' => route('settings.locationArchives.destroy', $location->id),
                'inheritNote' => $this->inheritNote($location),
            ];
        }

        return $drawer;
    }

    /**
     * The office the panel should open on when the page is drawn. Nothing,
     * usually, and the one a failed save came back from otherwise, so the edit
     * is still there to correct rather than lost behind a closed panel.
     */
    public function openLocationId(): ?int
    {
        $id = old('location_id');

        return $id === null ? null : (int) $id;
    }

    /**
     * What the fields of the panel hold when the page is drawn. Empty, usually,
     * since the panel fills them from the office that was clicked, and whatever
     * was typed when a save came back rejected otherwise.
     *
     * @return array{name: string, country: string, city: string, address: string, timezone: string, isPrimary: bool}
     */
    public function openLocationForm(): array
    {
        return [
            'name' => (string) old('name', ''),
            'country' => (string) old('country', ''),
            'city' => (string) old('city', ''),
            'address' => (string) old('address', ''),
            'timezone' => (string) old('timezone', ''),
            'isPrimary' => old('is_primary') !== null,
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
        return route('settings.locations.create');
    }

    public function companyHasNoOffice(): bool
    {
        return $this->locations()->isEmpty();
    }

    /**
     * The one thing worth saying about an office beside its name. An archived
     * office says so rather than saying it is the head office, since nobody can
     * be attached to it either way.
     */
    private function badge(Location $location): string
    {
        if ($location->isArchived()) {
            return __('Archived');
        }

        return $location->is_primary ? __('Head office') : '';
    }

    /**
     * The city and the country of an office, as one line, skipping whichever of
     * the two was never written down.
     */
    private function place(Location $location): string
    {
        $parts = array_filter([$location->city, $location->country]);

        return $parts === [] ? __('somewhere unrecorded') : implode(', ', $parts);
    }

    /**
     * The line under the fields of the panel, saying what an office hands down to
     * whoever works there.
     */
    private function inheritNote(Location $location): string
    {
        if ($location->country === null && $location->timezone === null) {
            return __('Fill in the country and the time zone, and the people who work here inherit both.');
        }

        return __('Somebody working here inherits :country and :timezone. Both stay changeable on their own record.', [
            'country' => $location->country ?? __('no country'),
            'timezone' => $location->timezone ?? __('no time zone'),
        ]);
    }

    /**
     * The offices the list shows: the ones the scope asks for, narrowed by the
     * search, in the order the toggle above the list is set to.
     *
     * @return Collection<int, Location>
     */
    private function filtered(): Collection
    {
        $locations = $this->locations()->filter(fn (Location $location): bool => match ($this->scope) {
            LocationScopeEnum::Active => ! $location->isArchived(),
            LocationScopeEnum::Archived => $location->isArchived(),
            LocationScopeEnum::All => true,
        });

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);

            $locations = $locations->filter(
                fn (Location $location): bool => str_contains(
                    mb_strtolower($location->name.' '.$location->city.' '.$location->country),
                    $needle,
                ),
            );
        }

        return $locations->sortBy(
            fn (Location $location): string => $this->sort === 'place'
                ? mb_strtolower($this->place($location))
                : mb_strtolower($location->name),
        );
    }

    /**
     * A link to one of the three lists, keeping whatever the current one was
     * narrowed and ordered by so switching scope does not throw a search away.
     *
     * @param  array<string, string>  $overrides
     */
    private function url(LocationScopeEnum $scope, array $overrides = []): string
    {
        $query = array_filter([
            'q' => $this->search,
            'sort' => $this->sort === 'name' ? '' : $this->sort,
            ...$overrides,
        ]);

        return route('settings.locations.index', array_filter([
            'scope' => $scope->segment(),
            ...$query,
        ]));
    }

    /**
     * @return Collection<int, Location>
     */
    private function locations(): Collection
    {
        return $this->locations ??= $this->user->company->locations()->get();
    }
}
