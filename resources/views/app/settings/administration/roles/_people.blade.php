{{--
  Who holds the role, and the two things that can be done about it: hand it to
  somebody else, and take it back.

  Handing it out opens a list of the colleagues who do not hold it yet, in place,
  rather than sending anybody to a screen of their own. A company where everybody
  already holds it has nothing to open, so the button says so instead.

  Both forms sit outside the form that saves the matrix, since a form cannot hold
  another and either of them would otherwise submit it.

  @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel
  @var array $role
--}}
@php
  $people = $viewModel->people();
  $assignable = $viewModel->assignable();
@endphp

<div x-data="{ assigning: false }" class="mt-9">
  <div class="flex flex-wrap items-end gap-x-4 gap-y-2">
    <div class="space-y-0.75">
      <h2 class="text-sm font-semibold tracking-tight text-ink">{{ __('People with this role') }}</h2>

      <p class="text-xs text-muted">{{ __('Handing a role out and taking it back are both written to the logs. A role somebody holds cannot be deleted.') }}</p>
    </div>

    <x-button.secondary
      type="button"
      x-show="! assigning"
      x-on:click="assigning = true"
      :disabled="$assignable === []"
      class="ml-auto py-1.75 text-xs [:where(&)]:px-3"
    >{{ $assignable === [] ? __('Everybody holds it') : __('Assign people') }}</x-button.secondary>
  </div>

  <div class="mt-2.75 rounded-xl border border-hairline bg-canvas">
    <div x-cloak x-show="assigning" class="border-b border-hairline-soft p-3.5">
      <x-form method="post" :action="$role['assignUrl']" class="flex flex-wrap items-end gap-3">
        <div class="min-w-0 flex-1 space-y-1.5">
          <label for="user" class="block text-xs text-body">{{ __('Who should hold it') }}</label>

          <select
            id="user"
            name="user"
            required
            class="block w-full rounded-md border border-hairline-strong bg-input px-3 py-2.5 text-base text-ink outline-none focus:border-focus focus:ring-2 focus:ring-focus sm:text-sm"
          >
            @foreach ($assignable as $person)
              <option value="{{ $person['id'] }}">{{ $person['name'] }} ({{ $person['email'] }})</option>
            @endforeach
          </select>
        </div>

        <div class="flex gap-2">
          <x-button.secondary type="button" x-on:click="assigning = false">{{ __('Cancel') }}</x-button.secondary>

          <x-button>{{ __('Assign') }}</x-button>
        </div>
      </x-form>
    </div>

    @forelse ($people as $person)
      <x-box.row class="flex flex-wrap items-center gap-x-3 gap-y-2">
        <x-avatar :employee="$person['employee']" :name="$person['name']" :size="30" />

        <div class="min-w-0">
          <p class="truncate text-sm font-medium text-ink">{{ $person['name'] }}</p>

          <p class="truncate text-xs text-muted">{{ $person['email'] }}</p>
        </div>

        <div class="ml-auto flex items-center gap-3">
          <p class="hidden text-xs text-muted-soft sm:block">{{ $person['since'] }}</p>

          <x-form method="delete" :action="$person['removeUrl']">
            <button
              type="submit"
              class="cursor-pointer rounded-md px-1.5 py-1 text-xs text-error transition-colors hover:bg-hover"
            >{{ __('Remove') }}</button>
          </x-form>
        </div>
      </x-box.row>
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
</div>
