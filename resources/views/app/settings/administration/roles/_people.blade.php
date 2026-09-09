{{-- Who holds the role, and the two things that can be done about it. --}}
{{--
  Handing it out opens a dialog listing the colleagues who do not hold it yet. A
  company where everybody already holds it has nobody to list, so the button says
  so instead.

  Taking it back asks first, in the row itself: it withdraws permissions from
  somebody who is working, and there is no undo behind it.

  @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel
  @var array $role
--}}
@php
  $people = $viewModel->people();
  $assignable = $viewModel->assignable();
@endphp

<div x-data="{ assigning: false }">
  <div class="mb-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
    <h2 class="text-[22px] leading-tight font-bold tracking-tight text-ink">{{ __('Held by') }}</h2>

    <x-button.secondary type="button" x-on:click="assigning = true" :disabled="$assignable === []">
      {{ $assignable === [] ? __('Everybody holds it') : __('Assign people') }}
    </x-button.secondary>
  </div>

  <div class="rounded-[18px] bg-canvas px-2.5 py-2 ring-[1.5px] ring-hairline">
    @forelse ($people as $person)
      <div x-data="{ confirming: false }" class="flex flex-wrap items-center gap-x-3.5 gap-y-2 rounded-xl px-3 py-2.75">
        <x-avatar :employee="$person['employee']" :name="$person['name']" :size="34" />

        <div class="min-w-0 flex-1">
          <p class="truncate text-base font-semibold text-ink">{{ $person['name'] }}</p>

          <p class="truncate text-sm text-muted">{{ $person['since'] }}</p>
        </div>

        <button
          type="button"
          x-show="! confirming"
          x-on:click="confirming = true"
          class="cursor-pointer rounded-md px-1.5 py-1 text-[15px] font-semibold text-muted transition-colors hover:text-error"
        >{{ __('Remove') }}</button>

        <div x-cloak x-show="confirming" class="flex w-full flex-wrap items-center justify-end gap-x-3 gap-y-1.5">
          <p class="text-sm text-error">{{ __('They lose whatever only this role granted them. Sure?') }}</p>

          <x-form method="delete" :action="$person['removeUrl']">
            <button
              type="submit"
              class="cursor-pointer rounded-md px-1.5 py-1 text-[15px] font-semibold text-error transition-colors hover:underline"
            >{{ __('Take it back') }}</button>
          </x-form>

          <button
            type="button"
            x-on:click="confirming = false"
            class="cursor-pointer rounded-md px-1.5 py-1 text-[15px] font-semibold text-muted transition-colors hover:text-ink"
          >{{ __('Keep it') }}</button>
        </div>
      </div>
    @empty
      <x-empty-state :title="__('Nobody holds this role yet')">
        <x-slot:icon>
          <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4">
            <circle cx="8" cy="6" r="2.6"></circle>
            <path d="M3 14c0-2.6 2.2-4 5-4s5 1.4 5 4"></path>
          </svg>
        </x-slot:icon>

        {{ __('Assign it to somebody to put its permissions to work. Until then it grants nothing to anybody.') }}
      </x-empty-state>
    @endforelse
  </div>

  <p class="mt-3 text-sm leading-relaxed text-pretty text-muted">
    {{ __('Handing a role out and taking it back are both written to the logs, on both sides.') }}
  </p>

  @include('app.settings.administration.roles._assign-people', ['viewModel' => $viewModel, 'role' => $role, 'assignable' => $assignable])
</div>
