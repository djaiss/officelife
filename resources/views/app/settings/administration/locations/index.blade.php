{{--
  The screen where the offices of a company are looked after: the counts at the
  top, then every office as a row, and the one being edited in a panel that
  slides in from the right rather than on a page of its own.

  It is big enough that it takes the window rather than a panel in the middle of
  the settings, which is what the stacked layer around it is for.

  Which offices are listed is the last segment of the path, so the open ones, the
  archived ones and all of them are three pages that can be linked to. The search
  and the sort refine whichever of the three is being read, so they travel in the
  query string and every link on the screen carries them along.

  The state is declared on the layout tag, so it is in scope for the whole
  screen: the save bar the layout draws in its sticky header watches a form
  inside the panel further down, and the button that opens the new office dialog
  sits in the header while the dialog itself is the last thing on the page. The
  save bar and the form it saves are not nested, so the button reaches the form
  through the html `form` attribute rather than by sitting inside it.

  Every office the table shows is handed to the browser at once, in the block of
  json further down, so opening the panel is a click rather than another trip to
  the server. A company keeps a handful of offices, not a warehouse of them.

  Saving goes over ajax, and the answer swaps the counts, the table and that
  block of json back in. The screen therefore never redraws itself out of what it
  already had: what the panel reads after a save is what the server just wrote,
  the same as if the page had been asked for again.

  @var \App\ViewModels\Settings\Administration\LocationsViewModel $viewModel
--}}
@php
  /* The state is built here rather than written into the tag, because blade
     compiles neither `@js` nor `{!! !!}` inside the attribute of a component,
     and the offices have to reach the browser as json. The `:` in front of the
     attribute below is what hands this string over as an expression. */
  $open = json_encode($viewModel->openLocationId(), JSON_THROW_ON_ERROR);
  $form = json_encode($viewModel->openLocationForm(), JSON_THROW_ON_ERROR);
  $creating = $errors->createLocation->any() ? 'true' : 'false';

  $screen = <<<JS
    {
      offices: {},
      open: {$open},
      form: {$form},
      creating: {$creating},
      confirming: false,
      refresh() {
        this.offices = JSON.parse(document.getElementById('locations-data').textContent)
      },
      get office() {
        return this.open === null ? null : (this.offices[this.open] ?? null)
      },
      get dirty() {
        const office = this.office

        if (! office) {
          return false
        }

        return ['name', 'country', 'city', 'address', 'timezone'].some(field => this.form[field] !== office[field])
          || this.form.isPrimary !== office.isPrimary
      },
      edit(id) {
        const office = this.offices[id]

        this.open = id
        this.confirming = false
        this.form = {
          name: office.name,
          country: office.country,
          city: office.city,
          address: office.address,
          timezone: office.timezone,
          isPrimary: office.isPrimary,
        }
      },
      discard() {
        this.edit(this.open)
      },
      close() {
        this.open = null
        this.confirming = false
      },
    }
    JS;
@endphp

<x-stacked-layer-layout
  :title="__('Locations')"
  :back-title="__('Settings')"
  :back-url="route('settings.profile.index')"
  :x-data="$screen"
  x-init="refresh()"
>
  <x-slot:header>
    <div class="flex min-w-0 items-center gap-2.25">
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="shrink-0 text-ink" aria-hidden="true">
        <path d="M8 14s4.4-4 4.4-7A4.4 4.4 0 0 0 3.6 7c0 3 4.4 7 4.4 7Z"></path>
        <circle cx="8" cy="6.8" r="1.7"></circle>
      </svg>

      <h1 class="truncate text-sm font-semibold tracking-tight text-ink">{{ __('Locations') }}</h1>

      <p class="hidden border-l border-hairline pl-2.25 text-xs whitespace-nowrap text-muted-soft lg:block">
        {{ __('Level 2 of :screen', ['screen' => __('Settings')]) }}
      </p>
    </div>
  </x-slot:header>

  <x-slot:actions>
    <x-button type="button" x-on:click="creating = true" class="py-1.5 text-xs [:where(&)]:px-3.25">
      {{ __('Add location') }}
    </x-button>
  </x-slot:actions>

  <div class="mx-auto max-w-260 space-y-4.5 px-4 pt-6 pb-22 sm:px-7">
    <div>
      <x-page-header :title="__('Locations')" />

      <p class="mt-1.5 max-w-165 text-sm text-body">
        {{ __('The company owns its offices, and an employee only points at one. Somebody fully remote has no office at all: their country and their time zone live on their own record.') }}
      </p>
    </div>

    @include('app.settings.administration.locations._stats', ['viewModel' => $viewModel])

    @include('app.settings.administration.locations._filters', ['viewModel' => $viewModel])

    @include('app.settings.administration.locations._table', ['viewModel' => $viewModel])

    {{-- Where the panel reads the offices from, and what a save swaps back in. --}}
    <script type="application/json" id="locations-data">@json($viewModel->drawer())</script>

    <x-notice>
      {{ __('Archiving an office keeps everything written about it. It leaves the list, so nobody can be sent to a desk that is no longer rented, and comes back whole if the company opens it again.') }}
    </x-notice>
  </div>

  @include('app.settings.administration.locations._drawer', ['viewModel' => $viewModel])

  @include('app.settings.administration.locations._create-location', ['viewModel' => $viewModel])
</x-stacked-layer-layout>
