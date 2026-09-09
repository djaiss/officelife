{{-- The one thing that can be done to a role as a whole and does not belong beside the others: deleting it. --}}
{{--
  Deleting takes a set of permissions away from everybody at once, so it asks
  first, the way revoking an API key does. A role somebody still holds, or one
  the application looks after itself, cannot go at all: the entry says why rather
  than disappearing, so nobody is left wondering where it went.

  The menu carries `data-escape-guard` while it is open, which is what stops the
  layer around it reading escape as "go back" before the menu has closed.

  @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel
  @var array $role
--}}
<div
  x-data="{ open: false, confirming: false }"
  x-on:click.outside="open = false; confirming = false"
  x-on:keydown.escape="if (open) { open = false; confirming = false }"
  :data-escape-guard="open ? '' : null"
  class="relative"
>
  <button
    type="button"
    x-on:click="open = ! open; confirming = false"
    :aria-expanded="open ? 'true' : 'false'"
    aria-label="{{ __('More about this role') }}"
    class="flex size-11 cursor-pointer items-center justify-center rounded-[10px] border-[1.5px] border-hairline-strong bg-card text-body transition-colors hover:border-ink hover:bg-hover hover:text-ink"
  >
    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
      <circle cx="3.6" cy="8" r="1.35"></circle>
      <circle cx="8" cy="8" r="1.35"></circle>
      <circle cx="12.4" cy="8" r="1.35"></circle>
    </svg>
  </button>

  <div
    x-cloak
    x-show="open"
    x-transition.opacity.duration.120ms
    class="absolute top-full right-0 z-20 mt-2 w-72 rounded-[18px] bg-canvas p-2 shadow-2xl ring-[1.5px] ring-hairline"
  >
    <div x-show="! confirming">
      @if ($viewModel->canBeDeleted())
        <button
          type="button"
          x-on:click="confirming = true"
          class="flex w-full cursor-pointer items-center rounded-xl px-3 py-2.5 text-left text-[15px] font-semibold text-error transition-colors hover:bg-hover"
        >{{ __('Delete this role') }}</button>
      @else
        <p class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-[15px] text-muted-soft">
          {{ __('Delete this role') }}

          <span class="ml-auto text-sm">{{ $viewModel->deleteHint() }}</span>
        </p>
      @endif
    </div>

    <div x-cloak x-show="confirming" class="space-y-3 p-2">
      <p class="text-sm leading-relaxed text-pretty text-error">
        {{ __('Whatever this role granted, it grants no longer. This cannot be undone. Sure?') }}
      </p>

      <div class="flex flex-col-reverse gap-2.5">
        <x-button.secondary type="button" x-on:click="confirming = false">{{ __('Keep it') }}</x-button.secondary>

        <x-form method="delete" :action="$role['destroyUrl']">
          <x-button class="w-full bg-error hover:bg-error/88">{{ __('Delete this role') }}</x-button>
        </x-form>
      </div>
    </div>
  </div>
</div>
