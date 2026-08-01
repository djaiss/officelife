{{--
  One email we sent, as a row in a list. Clicking it opens the copy that was
  sent, so somebody can tell what an email actually said rather than only that
  it left.

  The dot on the left is the delivery: still on its way, delivered, or bounced.
  Colour alone would say nothing to a screen reader, so the same three words are
  written out beside it.

  @var \App\Models\EmailSent $emailSent
--}}
@props(['emailSent'])

@php
  [$delivery, $deliveryLabel] = match (true) {
    $emailSent->bounced_at !== null => ['bg-error', __('Bounced')],
    $emailSent->delivered_at !== null => ['bg-success', __('Delivered')],
    default => ['bg-warning', __('On its way')],
  };
@endphp

<div x-data="{ open: false }" class="border-b border-hairline-soft last:border-b-0">
  <button
    type="button"
    @click="open = ! open"
    :aria-expanded="open ? 'true' : 'false'"
    class="flex w-full cursor-pointer items-start gap-3 px-4 py-[14px] text-left transition-colors duration-150 hover:bg-hover focus-visible:ring-2 focus-visible:ring-focus focus-visible:outline-none"
  >
    <span class="mt-[5px] size-2 shrink-0 rounded-full {{ $delivery }}" aria-hidden="true"></span>
    <span class="sr-only">{{ $deliveryLabel }}</span>

    <span class="min-w-0 text-[13px] leading-relaxed">
      <span class="block text-muted">{{ __('To:') }} {{ $emailSent->email_address }}</span>
      <span class="block text-ink">{{ __('Subject:') }} <span class="font-semibold">{{ $emailSent->subject }}</span></span>
    </span>

    <span class="ml-auto flex shrink-0 items-center gap-2 text-[12.5px] text-muted">
      @if ($emailSent->sent_at)
        <time datetime="{{ $emailSent->sent_at->toIso8601String() }}" title="{{ $emailSent->sent_at->toDayDateTimeString() }}">
          {{ __('Sent :time', ['time' => $emailSent->sent_at->diffForHumans()]) }}
        </time>
      @endif

      <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="transition-transform duration-200" :class="open && 'rotate-180'" aria-hidden="true">
        <path d="M4 6.4 8 10.4l4-4"></path>
      </svg>
    </span>
  </button>

  <div x-cloak x-show="open" x-transition class="border-t border-hairline-soft bg-card px-4 py-[14px]">
    <p class="text-center text-[12px] text-muted italic">{{ __('We remove the links from this copy, since they have probably expired.') }}</p>

    {{-- The copy arrives as the bare paragraphs Purify left behind, so the rhythm between them is ours to give. --}}
    <div class="mt-3 space-y-2 text-[13px] leading-relaxed text-body">{!! $emailSent->body !!}</div>
  </div>
</div>
