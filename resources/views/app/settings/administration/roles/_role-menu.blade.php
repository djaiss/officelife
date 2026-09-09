{{-- The one thing that can be done to a role as a whole and does not belong beside the others: deleting it. --}}
{{--
  Deleting takes a set of permissions away from everybody at once, so it asks in
  a dialog of its own. That dialog is the last thing on the screen rather than
  anything inside this menu, since the menu closes the moment it opens: what is
  read here is only `deleting`, which the screen declares.

  A role somebody still holds, or one the application looks after itself, cannot
  go at all: the entry says why rather than disappearing, so nobody is left
  wondering where it went.

  The menu carries `data-escape-guard` while it is open, which is what stops the
  layer around it reading escape as "go back" before the menu has closed.

  @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel
  @var array $role
--}}
<div
  x-data="{ open: false }"
  x-on:click.outside="open = false"
  x-on:keydown.escape="if (open) { open = false }"
  :data-escape-guard="open ? '' : null"
  class="relative"
>
  <button
    type="button"
    x-on:click="open = ! open"
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
    @if ($viewModel->canBeDeleted())
      <button
        type="button"
        x-on:click="open = false; deleting = true"
        class="flex w-full cursor-pointer items-center rounded-xl px-3 py-2.5 text-left text-[15px] font-semibold text-error transition-colors hover:bg-hover"
      >{{ __('Delete this role') }}</button>
    @else
      <p class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-[15px] text-muted-soft">
        {{ __('Delete this role') }}

        <span class="ml-auto text-sm">{{ $viewModel->deleteHint() }}</span>
      </p>
    @endif
  </div>
</div>
