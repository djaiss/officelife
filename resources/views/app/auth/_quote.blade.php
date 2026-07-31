{{--
  The panel on the side of the guest screens, carrying one line from The Office.

  @var array{text: string, author: string, source: string} $quote
--}}
<div class="quote-panel hidden items-center justify-center border-l border-hairline p-[60px] lg:flex">
  <div class="w-full max-w-[520px]">
    <div class="quote-card rounded-[15px] border border-hairline bg-canvas px-[26px] pt-[26px] pb-[22px]">
      <p class="text-[22px] leading-[1.42] tracking-[-0.018em] text-pretty text-ink">&ldquo;{{ $quote['text'] }}&rdquo;</p>

      <div class="mt-5 flex items-center gap-[11px]">
        <x-avatar-initials :name="$quote['author']" />

        <div>
          <div class="text-[13.5px] font-semibold text-ink">{{ $quote['author'] }}</div>
          <div class="mt-[2px] text-[12.5px] text-muted"><em>{{ __('from') }}</em> {{ $quote['source'] }}</div>
        </div>
      </div>
    </div>
  </div>
</div>
