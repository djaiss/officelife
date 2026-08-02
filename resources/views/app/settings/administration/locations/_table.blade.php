{{--
  Every office of the list, one to a row.

  A row is a button rather than a link, since what it opens is the panel to the
  right of the same page and not another page. The two headings that can order
  the table are links, because an order is a page that can be linked to.

  The tile at the left of a row is the two letter country code. An office nobody
  has written a country down for still gets a tile, so the rows keep their shape.

  The whole block is the target of the save in the panel, which is why it has an
  id: a rename has to show up in the row behind the panel without the screen
  being drawn again.

  Four columns need a screen wide enough for four columns. Below md the same row
  folds into two lines instead of squeezing every one of them into nothing: the
  office and its state on the first, where it is and what time it keeps on the
  second. The cells are placed by hand there, since the order they fold into is
  not the order they are written in.

  @var \App\ViewModels\Settings\Administration\LocationsViewModel $viewModel
--}}
@php
  $columns = 'grid-cols-[minmax(0,1fr)_auto] gap-x-4 gap-y-1.5 md:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_minmax(0,1fr)_auto] md:gap-y-0';
  $sortLinks = $viewModel->sortLinks();
@endphp

<div id="locations-table" class="space-y-4.5 transition-opacity [&[aria-busy]]:opacity-60">
  <div class="overflow-hidden rounded-xl border border-hairline bg-canvas">
    <div class="grid {{ $columns }} items-center border-b border-hairline-soft bg-sunken px-4 py-2.25 text-xs tracking-wide text-muted-soft uppercase">
      <a href="{{ $sortLinks['name']['url'] }}" data-turbo="true" class="flex items-center gap-1.5 hover:text-ink">
        {{ __('Office') }}

        <span aria-hidden="true">{{ $sortLinks['name']['arrow'] }}</span>
      </a>

      <a href="{{ $sortLinks['place']['url'] }}" data-turbo="true" class="flex items-center gap-1.5 hover:text-ink max-md:justify-end">
        {{ __('City and country') }}

        <span aria-hidden="true">{{ $sortLinks['place']['arrow'] }}</span>
      </a>

      <span class="max-md:hidden">{{ __('Time zone') }}</span>

      <span class="max-md:hidden">{{ __('Status') }}</span>
    </div>

    @forelse ($viewModel->rows() as $row)
      <button
        type="button"
        x-on:click="edit({{ $row['id'] }})"
        :class="open === {{ $row['id'] }} && 'bg-hover'"
        class="grid w-full {{ $columns }} cursor-pointer items-center border-b border-hairline-soft px-4 py-2.75 text-left transition-colors last:border-b-0 hover:bg-hover"
      >
        <span class="flex min-w-0 items-center gap-2.75 max-md:col-start-1 max-md:row-start-1">
          <span class="flex size-7.5 shrink-0 items-center justify-center rounded-md text-xs font-semibold {{ $row['isArchived'] ? 'bg-hover text-muted-soft' : 'bg-brand/12 text-brand' }}">
            {{ $row['code'] }}
          </span>

          <span class="min-w-0">
            <span class="flex items-center gap-1.75">
              <span class="truncate text-sm font-medium text-ink">{{ $row['name'] }}</span>

              @if ($row['isPrimary'])
                <span class="shrink-0 rounded-full bg-hover px-1.75 py-0.25 text-xs text-body">{{ __('HQ') }}</span>
              @endif
            </span>

            <span class="mt-0.25 block truncate text-xs text-muted-soft">{{ $row['address'] }}</span>
          </span>
        </span>

        <span class="truncate text-sm text-body max-md:col-start-1 max-md:row-start-2 max-md:pl-10.25 max-md:text-xs">{{ $row['place'] }}</span>

        <span class="truncate text-sm text-body max-md:col-start-2 max-md:row-start-2 max-md:text-right max-md:text-xs">{{ $row['timezone'] }}</span>

        <span class="flex items-center gap-2.5 max-md:col-start-2 max-md:row-start-1 max-md:justify-end">
          <span class="inline-flex items-center gap-1.75 rounded-full px-2.25 py-0.75 text-xs whitespace-nowrap {{ $row['isArchived'] ? 'bg-hover text-body' : 'bg-success/12 text-success' }}">
            <span class="size-1.25 rounded-full {{ $row['isArchived'] ? 'bg-muted-soft' : 'bg-success' }}" aria-hidden="true"></span>

            {{ $row['status'] }}
          </span>

          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" class="shrink-0 text-placeholder" aria-hidden="true">
            <path d="M6.4 4.4 10 8l-3.6 3.6"></path>
          </svg>
        </span>
      </button>
    @empty
      @if ($viewModel->companyHasNoOffice())
        <x-empty-state :title="__('This company has no office')">
          <x-slot:icon>
            <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4">
              <path d="M8 14s4.4-4 4.4-7A4.4 4.4 0 0 0 3.6 7c0 3 4.4 7 4.4 7Z"></path>
              <circle cx="8" cy="6.8" r="1.7"></circle>
            </svg>
          </x-slot:icon>

          {{ __('A company that works entirely remotely needs none. Add one as soon as it rents a desk somewhere, and people can be sent to it.') }}
        </x-empty-state>
      @else
        <x-empty-state :title="__('No office matches this search')">
          <x-slot:icon>
            <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4">
              <circle cx="7.2" cy="7.2" r="4.2"></circle>
              <line x1="10.4" y1="10.4" x2="13.4" y2="13.4"></line>
            </svg>
          </x-slot:icon>

          {{ __('Archived offices are kept off this list. Switch to the archived ones, or to all of them, if that is what you are looking for.') }}
        </x-empty-state>
      @endif
    @endforelse
  </div>

  <p class="text-xs text-muted-soft">{{ $viewModel->header() }}</p>
</div>
