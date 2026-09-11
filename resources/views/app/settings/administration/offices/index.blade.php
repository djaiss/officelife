{{-- The offices a company works from, as a list with a panel over it. --}}
{{-- @var \App\ViewModels\Settings\Administration\OfficesViewModel $viewModel --}}
@php
  /* The state is built here rather than written into the tag, because blade
     compiles neither `@js` nor `{!! !!}` inside the attribute of a component,
     and the offices have to reach the browser as json. */
  $open = json_encode($viewModel->openOfficeId(), JSON_THROW_ON_ERROR);
  $form = json_encode($viewModel->openOfficeForm(), JSON_THROW_ON_ERROR);
  $creating = $errors->createOffice->any() ? 'true' : 'false';

  $screen = <<<JS
    {
      offices: {},
      open: {$open},
      form: {$form},
      creating: {$creating},
      archiving: false,
      refresh() {
        this.offices = JSON.parse(document.getElementById('offices-data').textContent)
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
          isHeadOffice: office.isHeadOffice,
        }
      },
      close() {
        this.open = null
        this.archiving = false
      },
    }
    JS;
@endphp

<x-top-bar-layout :title="__('Offices')">
  <x-slot:top-bar>
    <x-top-bar :company-name="$viewModel->companyName()" :name="$viewModel->name()" :employee="$viewModel->employee()" />
  </x-slot:top-bar>

  <nav class="mt-5.5 mb-6.5 flex items-center gap-2.25 text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
    <a href="{{ route('home.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Dashboard') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <a href="{{ route('settings.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Settings') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <span class="font-medium text-ink" aria-current="page">{{ __('Offices') }}</span>
  </nav>

  <div x-data="{{ $screen }}" x-init="refresh()">
    <div class="mb-9 grid gap-6 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start">
      <div>
        <h1 class="mb-2 text-4xl leading-tight font-bold tracking-tight text-ink">{{ __('Offices') }}</h1>

        <p class="text-lg leading-normal text-pretty text-body">
          {{ __('The company owns its offices, and an employee only points at one. Somebody fully remote has no office at all: their country and their time zone live on their own record.') }}
        </p>
      </div>

      <x-button type="button" x-on:click="creating = true" class="max-sm:w-full">{{ __('Add an office') }}</x-button>
    </div>

    @include('app.settings.administration.offices._statistics', ['viewModel' => $viewModel])

    @include('app.settings.administration.offices._filters', ['viewModel' => $viewModel])

    @include('app.settings.administration.offices._table', ['viewModel' => $viewModel])

    <script type="application/json" id="offices-data">@json($viewModel->drawer())</script>

    <p class="mt-4 text-sm leading-relaxed text-pretty text-muted">
      {{ __('Archiving an office keeps everything written about it. It leaves the list, so nobody can be sent to a desk that is no longer rented, and comes back whole if the company opens it again.') }}
    </p>

    @include('app.settings.administration.offices._edit-office', ['viewModel' => $viewModel])

    @include('app.settings.administration.offices._archive-office')

    @include('app.settings.administration.offices._create-office', ['viewModel' => $viewModel])
  </div>
</x-top-bar-layout>
