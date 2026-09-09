{{-- The dialog that asks before something is taken away for good. --}}
{{--
  It sits in the middle of the window rather than beside the button that opened
  it, so what is about to happen is read before anything else can be clicked.

  What opens and closes it is the caller's, passed in as two alpine expressions.
  The component owns none of that state, so a list can drive one dialog per row
  with the same pair of expressions.

  It appears at once rather than fading in. `x-transition` on it never completes:
  the enter transition is queued behind a cascade it never gets out of, and the
  dialog stays at `display: none` with the state already true. A question about
  something irreversible is also the wrong place for a hundred milliseconds of
  fade, so this is left as it is rather than worked around.

  It carries `data-escape-guard` while it is open, so escape closes the dialog
  rather than leaving the layer underneath it.

  Clicking away closes it too, which is read off the backdrop itself rather than
  as a click outside the panel: the click that opens the dialog is still on its
  way up the document when the dialog appears, and an outside handler would catch
  that one and close it again straight away.

  @var string $show
  @var string $close
  @var string $labelledby
  @var string $title
  @var \Illuminate\View\ComponentSlot $actions
--}}
@props([
  'show',
  'close',
  'labelledby',
  'title',
])

<div
  x-cloak
  x-show="{{ $show }}"
  x-on:keydown.escape="{{ $close }}"
  x-on:click.self="{{ $close }}"
  :data-escape-guard="{{ $show }} ? '' : null"
  class="fixed inset-0 z-70 flex justify-center overflow-y-auto bg-black/35 px-4 py-14"
>
  <div
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $labelledby }}"
    class="h-fit w-full max-w-115 rounded-[20px] bg-canvas px-6 pt-6 pb-7 shadow-2xl ring-[1.5px] ring-hairline sm:px-7"
  >
    <h2 id="{{ $labelledby }}" class="text-2xl font-bold tracking-tight text-ink">{{ $title }}</h2>

    <p class="mt-1.5 text-[15px] leading-relaxed text-pretty text-body">{{ $slot }}</p>

    <div class="mt-5.5 flex flex-wrap items-center gap-2.5">
      {{ $actions }}
    </div>
  </div>
</div>
