{{--
  One email we sent, as a row in a list. Clicking it opens the copy that was
  sent, so somebody can tell what an email actually said rather than only that
  it left.

  Two screens show this list, the logs and the page that holds every email, so
  the markup sits here rather than being written twice.

  The dot on the left is the delivery: still on its way, delivered, or bounced.
  Colour alone would say nothing to a screen reader, so the same three words are
  written out beside it.

  Above sm the row reads across, with the time and the chevron out to the right
  of the address and the subject. Below sm there is not enough width for that,
  so the time moves under the subject where the rest of the words are, and only
  the chevron stays on the right. The chevron is the whole reason somebody knows
  the row opens, so it holds its place against the first line rather than
  drifting down with the time.

  The button fills the row, so the row is asked for no padding of its own. The
  tint under the pointer stays on the row, which is the element that knows
  whether it is first or last and therefore how to round its corners. The copy
  below carries its own background, so it is not tinted along with it.

  @var \App\Models\EmailSent $emailSent
--}}
@php
  [$delivery, $deliveryLabel] = match (true) {
    $emailSent->bounced_at !== null => ['bg-error', __('Bounced')],
    $emailSent->delivered_at !== null => ['bg-success', __('Delivered')],
    default => ['bg-warning', __('On its way')],
  };

  $sentAt = $emailSent->sent_at;
  $sentAtLabel = $sentAt ? __('Sent :time', ['time' => $sentAt->diffForHumans()]) : null;
@endphp

<x-box.row x-data="{ open: false }" padding="p-0">
  <button
    type="button"
    @click="open = ! open"
    :aria-expanded="open ? 'true' : 'false'"
    class="flex w-full cursor-pointer items-start gap-x-3 px-4 py-3.5 text-left focus-visible:ring-2 focus-visible:ring-focus focus-visible:outline-none"
  >
    <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $delivery }}" aria-hidden="true"></span>
    <span class="sr-only">{{ $deliveryLabel }}</span>

    <span class="min-w-0 flex-1 text-sm leading-relaxed">
      {{-- An address has no break of its own, so it is told it may break anywhere rather than push the row wide. --}}
      <span class="block wrap-anywhere text-muted">{{ __('To:') }} {{ $emailSent->email_address }}</span>
      <span class="block text-ink">{{ __('Subject:') }} <span class="font-semibold">{{ $emailSent->subject }}</span></span>

      @if ($sentAt)
        <time datetime="{{ $sentAt->toIso8601String() }}" title="{{ $sentAt->toDayDateTimeString() }}" class="mt-1 block text-xs text-muted sm:hidden">{{ $sentAtLabel }}</time>
      @endif
    </span>

    <span class="flex shrink-0 items-center gap-2 text-xs text-muted">
      @if ($sentAt)
        <time datetime="{{ $sentAt->toIso8601String() }}" title="{{ $sentAt->toDayDateTimeString() }}" class="whitespace-nowrap max-sm:hidden">{{ $sentAtLabel }}</time>
      @endif

      <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="mt-0.5 transition-transform duration-200" :class="open && 'rotate-180'" aria-hidden="true">
        <path d="M4 6.4 8 10.4l4-4"></path>
      </svg>
    </span>
  </button>

  {{-- The copy takes the bottom corners of the row it sits in, so the last one open does not square off the panel. --}}
  <div x-cloak x-show="open" x-transition class="rounded-b-[inherit] border-t border-hairline-soft bg-card px-4 py-3.5">
    <p class="text-center text-xs text-muted italic">{{ __('We remove the links from this copy, since they have probably expired.') }}</p>

    {{-- The copy arrives as the bare paragraphs Purify left behind, so the rhythm between them is ours to give. --}}
    <div class="mt-3 space-y-2 text-sm leading-relaxed text-body">{!! $emailSent->body !!}</div>
  </div>
</x-box.row>
