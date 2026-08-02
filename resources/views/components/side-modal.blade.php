{{--
  A panel that slides in from the right of the window, over the screen it was
  opened from. It is for looking after one row of a list without leaving the list
  behind, which a dialog in the middle of the window would not do: the rows stay
  visible down the left, so moving from one to the next is one click.

  What opens and closes it is the caller's, passed in as two alpine expressions.
  The component owns none of that state, so a list can drive the panel with the
  id of whichever row is open and hand the same expression to `show`.

  It carries `data-escape-guard` while it is open, so escape closes the panel
  rather than leaving the layer underneath it.

  Clicking away closes it too, which is read off the backdrop itself rather than
  as a click outside the panel: the click that opens the panel is still on its
  way up the document when the panel appears, and an outside handler would catch
  that one and close it again straight away.

  The page behind it stops scrolling while it is open, so a wheel over the
  backdrop does not quietly move the list under the panel.

  @var string $show
  @var string $close
  @var string|null $labelledby
  @var \Illuminate\View\ComponentSlot|null $header
  @var \Illuminate\View\ComponentSlot|null $footer
--}}
@props([
  'show',
  'close',
  'labelledby' => null,
])

<div
  x-cloak
  x-show="{{ $show }}"
  x-transition.opacity.duration.110ms
  x-on:keydown.escape="{{ $close }}"
  x-on:click.self="{{ $close }}"
  x-effect="document.body.classList.toggle('overflow-hidden', !! ({{ $show }}))"
  :data-escape-guard="{{ $show }} ? '' : null"
  class="fixed inset-0 z-60 bg-black/25"
>
  <div
    x-show="{{ $show }}"
    x-transition:enter="transition duration-200 ease-out"
    x-transition:enter-start="translate-x-6 opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    role="dialog"
    aria-modal="true"
    @if ($labelledby) aria-labelledby="{{ $labelledby }}" @endif
    {{ $attributes->class('absolute inset-y-0 right-0 flex w-full max-w-130 flex-col border-l border-hairline-strong bg-page shadow-2xl') }}
  >
    @isset($header)
      <div class="flex shrink-0 items-center gap-3 border-b border-hairline bg-sunken px-4.5 py-3">
        {{ $header }}

        <button
          type="button"
          x-on:click="{{ $close }}"
          aria-label="{{ __('Close the panel') }}"
          class="ml-auto flex size-6.5 shrink-0 cursor-pointer items-center justify-center rounded-md text-muted transition-colors hover:bg-hover hover:text-ink"
        >
          <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <line x1="3.6" y1="3.6" x2="12.4" y2="12.4"></line>
            <line x1="12.4" y1="3.6" x2="3.6" y2="12.4"></line>
          </svg>
        </button>
      </div>
    @endisset

    <div class="min-h-0 flex-1 overflow-y-auto px-4.5 py-4.5">
      {{ $slot }}
    </div>

    @isset($footer)
      <div class="shrink-0 border-t border-hairline bg-sunken px-4.5 py-3">
        {{ $footer }}
      </div>
    @endisset
  </div>
</div>
