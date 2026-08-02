<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Administration;

use App\Enums\LocationScopeEnum;
use App\Models\Location;
use App\Models\User;
use DateTimeZone;
use Illuminate\Database\Eloquent\Collection;

/**
 * What the locations screen shows: the offices of the company as a table, with
 * the four counts above it, and everything the side modal needs to edit one of
 * them without asking the server for it.
 *
 * The scope is part of the path, so it says which of the three lists is being
 * read. The search and the sort refine that list rather than name a different
 * one, so they stay in the query string and every link the screen draws carries
 * them along.
 */
class LocationsViewModel
{
    /**
     * The offices of the company, asked for once and kept. The counts, the table
     * and the modal all read them, and each further ask would be another query.
     *
     * @var Collection<int, Location>|null
     */
    private ?Collection $locations = null;

    public function __construct(
        private readonly User $user,
        private readonly LocationScopeEnum $scope,
        private readonly string $search = '',
        private readonly string $sort = 'name',
        private readonly string $direction = 'asc',
    ) {}

    /**
     * The counts above the table. They describe the company rather than the list
     * below them, so narrowing the search does not move them.
     *
     * @return array<int, array{label: string, value: int, note: string}>
     */
    public function stats(): array
    {
        $open = $this->locations()->reject(fn (Location $location): bool => $location->isArchived());
        $archived = $this->locations()->count() - $open->count();

        return [
            [
                'label' => __('Offices'),
                'value' => $open->count(),
                'note' => $archived === 1
                    ? __('1 archived')
                    : __(':count archived', ['count' => $archived]),
            ],
            [
                'label' => __('Countries'),
                'value' => $open->pluck('country')->filter()->unique()->count(),
                'note' => __('where the company rents a desk'),
            ],
            [
                'label' => __('Time zones'),
                'value' => $open->pluck('timezone')->filter()->unique()->count(),
                'note' => __('the working day is spread over'),
            ],
            [
                'label' => __('Head office'),
                'value' => $open->where('is_primary', true)->count(),
                'note' => __('a company keeps one at most'),
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
     * The line above the table, which says how many offices are in it rather
     * than making somebody count the rows.
     */
    public function header(): string
    {
        $count = count($this->rows());

        return $count === 1
            ? __('1 office')
            : __(':count offices', ['count' => $count]);
    }

    /**
     * One row of the table. The two letter country code doubles as the tile at
     * the left of the row, which is why an office without a country still gets
     * something to show.
     *
     * @return array<int, array{id: int, name: string, code: string, address: string, place: string, timezone: string, isPrimary: bool, isArchived: bool, status: string}>
     */
    public function rows(): array
    {
        return array_values(array_map(fn (Location $location): array => [
            'id' => $location->id,
            'name' => $location->name,
            'code' => $location->country ?? '··',
            'address' => $location->address ?? __('no address on file'),
            'place' => $this->place($location),
            'timezone' => $location->timezone ?? __('same as the company'),
            'isPrimary' => $location->is_primary,
            'isArchived' => $location->isArchived(),
            'status' => $location->isArchived() ? __('Archived') : __('Open'),
        ], $this->filtered()->all()));
    }

    /**
     * Everything the side modal needs to edit an office, keyed by which office it
     * is. It goes into the page as one blob, so opening the modal is a click
     * rather than a page of its own.
     *
     * @return array<int, array{id: int, name: string, code: string, meta: string, country: string, city: string, address: string, timezone: string, isPrimary: bool, isArchived: bool, updateUrl: string, archiveUrl: string, restoreUrl: string, inheritNote: string}>
     */
    public function drawer(): array
    {
        $drawer = [];

        foreach ($this->filtered() as $location) {
            $drawer[$location->id] = [
                'id' => $location->id,
                'name' => $location->name,
                'code' => $location->country ?? '··',
                'meta' => $this->place($location).' · '.($location->isArchived() ? __('archived') : __('open')),
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
     * The office the modal should open on when the page is drawn. Nothing,
     * usually, and the one a failed save came back from otherwise, so the edit
     * is still there to correct rather than lost behind a closed modal.
     */
    public function openLocationId(): ?int
    {
        $id = old('location_id');

        return $id === null ? null : (int) $id;
    }

    /**
     * What the fields of the modal hold when the page is drawn. Empty, usually,
     * since the modal fills them from the office that was clicked, and whatever
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
     * Every time zone the world keeps, for the picker in the modal.
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
     * quietly put the table back in the order it starts in.
     *
     * @return array<string, string>
     */
    public function sortState(): array
    {
        return ['sort' => $this->sort, 'dir' => $this->direction];
    }

    /**
     * The two headings the table can be ordered by, each a link that turns the
     * order around when it is already the one in use.
     *
     * @return array<string, array{url: string, arrow: string}>
     */
    public function sortLinks(): array
    {
        $links = [];

        foreach (['name', 'place'] as $column) {
            $current = $this->sort === $column;

            $links[$column] = [
                'url' => $this->url($this->scope, [
                    'sort' => $column,
                    'dir' => $current && $this->direction === 'asc' ? 'desc' : 'asc',
                ]),
                'arrow' => $current ? ($this->direction === 'asc' ? '↑' : '↓') : '',
            ];
        }

        return $links;
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
     * The city and the country of an office, as one line, skipping whichever of
     * the two was never written down.
     */
    private function place(Location $location): string
    {
        $parts = array_filter([$location->city, $location->country]);

        return $parts === [] ? __('somewhere unrecorded') : implode(', ', $parts);
    }

    /**
     * The line under the fields of the modal, saying what an office hands down to
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
     * The offices the table shows: the ones the scope asks for, narrowed by the
     * search, in the order the headings were last clicked in.
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

        $sorted = $locations->sortBy(
            fn (Location $location): string => $this->sort === 'place'
                ? mb_strtolower($this->place($location))
                : mb_strtolower($location->name),
        );

        return $this->direction === 'desc' ? $sorted->reverse() : $sorted;
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
            'dir' => $this->direction === 'asc' ? '' : $this->direction,
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
