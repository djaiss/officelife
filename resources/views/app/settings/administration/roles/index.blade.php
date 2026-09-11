{{-- Every role of the company as a list, and the dialog that adds one to it. --}}
{{-- @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel --}}
<x-top-bar-layout :title="__('Roles')">
  <x-breadcrumb
    :trail="[
      __('Dashboard') => route('home.index'),
      __('Settings') => route('settings.index'),
      __('Roles') => null,
    ]"
  />

  <div x-data="{ creating: {{ $errors->createRole->any() ? 'true' : 'false' }} }">
    <!-- page title and the new role button -->
    <div class="mb-9 grid gap-6 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start">
      <div>
        <h1 class="mb-2 text-4xl leading-tight font-bold tracking-tight text-ink">{{ __('Roles') }}</h1>

        <p class="text-lg leading-normal text-pretty text-body">
          {{ __('A role is a set of things somebody is allowed to do. People can hold several at once, and what they get is everything their roles grant added together.') }}
        </p>
      </div>

      <x-button type="button" x-on:click="creating = true" class="max-sm:w-full">{{ __('New role') }}</x-button>
    </div>

    @include('app.settings.administration.roles._roles', ['viewModel' => $viewModel])

    <!-- note about roles -->
    <p class="mt-4 text-sm leading-relaxed text-pretty text-muted">
      {{ __('Handing a role out and taking it back are both written to the logs. A role somebody holds cannot be deleted.') }}
    </p>

    @include('app.settings.administration.roles._create-role', ['viewModel' => $viewModel])
  </div>
</x-top-bar-layout>
