{{-- Who holds the role, and the two things that can be done about it. --}}
{{--
  Handing it out opens a dialog listing the colleagues who do not hold it yet. A
  company where everybody already holds it has nobody to list, so the button says
  so instead.

  Taking it back asks first, in a dialog of its own: it withdraws permissions
  from somebody who is working, and the row it started from is too small to say
  what that costs them. `removing` holds whoever is being asked about rather than
  a yes or no, so one dialog per row can sit below the list instead of inside it.

  @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel
  @var array $role
--}}
@php
  $people = $viewModel->people();
  $assignable = $viewModel->assignable();
@endphp

<div x-data="{ assigning: false, removing: null }">
  <div class="mb-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
    <h2 class="text-[22px] leading-tight font-bold tracking-tight text-ink">{{ __('Held by') }}</h2>

    <x-button.secondary type="button" x-on:click="assigning = true" :disabled="$assignable === []">
      {{ $assignable === [] ? __('Everybody holds it') : __('Assign people') }}
    </x-button.secondary>
  </div>

  <div class="rounded-[18px] bg-canvas px-2.5 py-2 ring-[1.5px] ring-hairline">
    @forelse ($people as $person)
      <div class="flex flex-wrap items-center gap-x-3.5 gap-y-2 rounded-xl px-3 py-2.75">
        <x-avatar :employee="$person['employee']" :name="$person['name']" :size="34" />

        <div class="min-w-0 flex-1">
          <p class="truncate text-base font-semibold text-ink">{{ $person['name'] }}</p>

          <p class="truncate text-sm text-muted">{{ $person['since'] }}</p>
        </div>

        <button
          type="button"
          x-on:click="removing = {{ $person['id'] }}"
          class="cursor-pointer rounded-md px-1.5 py-1 text-[15px] font-semibold text-muted transition-colors hover:text-error"
        >{{ __('Remove') }}</button>
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

  @foreach ($people as $person)
    <x-confirm-dialog
      show="removing === {{ $person['id'] }}"
      close="removing = null"
      labelledby="remove-holder-{{ $person['id'] }}-title"
      :title="__('Take the role back from :name?', ['name' => $person['name']])"
      :cancel="__('Keep it')"
    >
      {{ __('They keep whatever their other roles grant. Anything only this role gave them, they lose.') }}

      <x-slot:actions>
        <x-form method="delete" :action="$person['removeUrl']">
          <x-button.danger>{{ __('Take it back') }}</x-button.danger>
        </x-form>
      </x-slot:actions>
    </x-confirm-dialog>
  @endforeach
</div>
