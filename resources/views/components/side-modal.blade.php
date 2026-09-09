{{-- A panel that slides in from the right of the window, over the screen it was opened from. --}}
{{--
  It is for looking after one row of a list without leaving the list behind,
  which a dialog in the middle of the window would not do: the rows stay visible
  down the left, so moving from one to the next is one click.

  It sits inside the window rather than against its edges, so the list it came
  from is still framed around it.

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
    {{ $attributes->class('absolute inset-y-3.5 right-3.5 flex w-[calc(100%-1.75rem)] max-w-110 flex-col overflow-hidden rounded-[20px] bg-canvas shadow-2xl ring-[1.5px] ring-hairline') }}
  >
    @isset($header)
      {{-- The close button is the last thing on the line whatever the header
           holds, so what the caller puts there wraps under itself on a narrow
           screen rather than pushing the way out of the panel off the edge. --}}
      <div class="flex shrink-0 items-start gap-4 px-5.5 pt-6 pb-5 sm:px-7">
        <div class="min-w-0 flex-1">
          {{ $header }}
        </div>

        <button
          type="button"
          x-on:click="{{ $close }}"
          aria-label="{{ __('Close the panel') }}"
          class="flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-lg text-muted transition-colors hover:bg-hover hover:text-ink"
        >
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <line x1="3.6" y1="3.6" x2="12.4" y2="12.4"></line>
            <line x1="12.4" y1="3.6" x2="3.6" y2="12.4"></line>
          </svg>
        </button>
      </div>
    @endisset

    <div class="min-h-0 flex-1 overflow-y-auto px-5.5 pb-7 sm:px-7">
      {{ $slot }}
    </div>
  </div>
</div>
