{{-- The offices a company works from: what it keeps, every office as a row, and the one being edited in a panel over the list. --}}
{{--
  The state is declared on a div wrapping the whole screen, so it is in scope
  everywhere it is read: the button that opens the new office dialog sits beside
  the title, and the dialog itself is the last thing on the page.

  Every office the list shows is handed to the browser at once, in the block of
  json further down, so opening the panel is a click rather than another trip to
  the server. A company keeps a handful of offices, not a warehouse of them.

  Saving goes over ajax, and the answer swaps the counts, the list and that block
  of json back in. The screen therefore never redraws itself out of what it
  already had: what the panel reads after a save is what the server just wrote,
  the same as if the page had been asked for again.

  @var \App\ViewModels\Settings\Administration\LocationsViewModel $viewModel
--}}
@php
  /* The state is built here rather than written into the tag, because blade
     compiles neither `@js` nor `{!! !!}` inside the attribute of a component,
     and the offices have to reach the browser as json. */
  $open = json_encode($viewModel->openLocationId(), JSON_THROW_ON_ERROR);
  $form = json_encode($viewModel->openLocationForm(), JSON_THROW_ON_ERROR);
  $creating = $errors->createLocation->any() ? 'true' : 'false';

  $screen = <<<JS
    {
      offices: {},
      open: {$open},
      form: {$form},
      creating: {$creating},
      archiving: false,
      refresh() {
        this.offices = JSON.parse(document.getElementById('locations-data').textContent)
      },
      get office() {
        return this.open === null ? null : (this.offices[this.open] ?? null)
      },
      edit(id) {
        const office = this.offices[id]

        this.open = id
        this.archiving = false
        this.form = {
          name: office.name,
          country: office.country,
          city: office.city,
          address: office.address,
          timezone: office.timezone,
          isPrimary: office.isPrimary,
        }
      },
      close() {
        this.open = null
        this.archiving = false
      },
    }
    JS;
@endphp

<x-top-bar-layout :title="__('Locations')">
  <x-slot:top-bar>
    <x-top-bar :company-name="$viewModel->companyName()" :name="$viewModel->name()" :employee="$viewModel->employee()" />
  </x-slot:top-bar>

  <nav class="mt-5.5 mb-6.5 flex items-center gap-2.25 text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
    <a href="{{ route('home.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Dashboard') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <a href="{{ route('settings.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Settings') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <span class="font-medium text-ink" aria-current="page">{{ __('Locations') }}</span>
  </nav>

  <div x-data="{{ $screen }}" x-init="refresh()">
    <div class="mb-9 grid gap-6 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start">
      <div>
        <h1 class="mb-2 text-4xl leading-tight font-bold tracking-tight text-ink">{{ __('Locations') }}</h1>

        <p class="text-lg leading-normal text-pretty text-body">
          {{ __('The company owns its offices, and an employee only points at one. Somebody fully remote has no office at all: their country and their time zone live on their own record.') }}
        </p>
      </div>

      <x-button type="button" x-on:click="creating = true" class="max-sm:w-full">{{ __('Add an office') }}</x-button>
    </div>

    @include('app.settings.administration.locations._stats', ['viewModel' => $viewModel])

    @include('app.settings.administration.locations._filters', ['viewModel' => $viewModel])

    @include('app.settings.administration.locations._table', ['viewModel' => $viewModel])

    {{-- Where the panel reads the offices from, and what a save swaps back in. --}}
    <script type="application/json" id="locations-data">@json($viewModel->drawer())</script>

    <p class="mt-4 text-sm leading-relaxed text-pretty text-muted">
      {{ __('Archiving an office keeps everything written about it. It leaves the list, so nobody can be sent to a desk that is no longer rented, and comes back whole if the company opens it again.') }}
    </p>

    @include('app.settings.administration.locations._drawer', ['viewModel' => $viewModel])

    @include('app.settings.administration.locations._archive-location')

    @include('app.settings.administration.locations._create-location', ['viewModel' => $viewModel])
  </div>
</x-top-bar-layout>
