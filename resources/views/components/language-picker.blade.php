{{--
  The picker for the language of the interface. It opens upwards, because it sits
  at the bottom of the guest screens.

  @var array<int, array{code: string, label: string, region: string, flag: string}> $locales
  @var string $current
--}}
@props([
  'locales',
  'current',
])

@php
  $flag = 'inline-block h-[13.5px] w-[19px] shrink-0 rounded-[3px] bg-cover shadow-[inset_0_0_0_1px_rgba(128,128,140,0.35)]';
  $selected = collect($locales)->firstWhere('code', $current) ?? $locales[0];
@endphp

<div x-data="{ open: false }" {{ $attributes->class(['relative']) }}>
  <button
    type="button"
    @click="open = ! open"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    :aria-expanded="open ? 'true' : 'false'"
    aria-haspopup="menu"
    :class="open ? 'border-focus' : 'border-hairline-strong'"
    class="flex cursor-pointer items-center gap-[9px] rounded-md border bg-canvas px-[11px] py-2 text-[13px] text-ink transition-colors"
  >
    <span class="{{ $flag }}" style="background: {{ $selected['flag'] }};" aria-hidden="true"></span>
    <span>{{ $selected['label'] }}</span>

    <svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" class="ml-[2px] text-muted" aria-hidden="true">
      <path d="M4.5 6.6 8 3.1l3.5 3.5"></path>
      <path d="M4.5 9.4 8 12.9l3.5-3.5"></path>
    </svg>
  </button>

  <div
    x-cloak
    x-show="open"
    x-transition.opacity.duration.120ms
    role="menu"
    class="absolute bottom-[calc(100%+6px)] left-0 z-10 w-[250px] overflow-hidden rounded-lg border border-hairline-strong bg-canvas shadow-lg"
  >
    <div class="px-3 pt-[10px] pb-[6px] text-[10.5px] tracking-[0.09em] text-muted-soft uppercase">
      {{ __('Interface language') }}
    </div>

    <x-form method="put" :action="route('auth.locale.update')">
      @foreach ($locales as $locale)
        <button
          type="submit"
          name="locale"
          value="{{ $locale['code'] }}"
          role="menuitem"
          class="flex w-full cursor-pointer items-center gap-[10px] px-3 py-[9px] text-left transition-colors hover:bg-hover {{ $locale['code'] === $current ? 'bg-card' : '' }}"
        >
          <span class="{{ $flag }}" style="background: {{ $locale['flag'] }};" aria-hidden="true"></span>
          <span class="flex-1 text-[13px] text-ink {{ $locale['code'] === $current ? 'font-semibold' : '' }}">{{ $locale['label'] }}</span>
          <span class="text-xs text-muted-soft">{{ $locale['region'] }}</span>

          <span class="flex w-[14px] items-center justify-center text-ink {{ $locale['code'] === $current ? '' : 'opacity-0' }}">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round">
              <path d="M2.2 6.3 4.7 8.8 9.8 3.4"></path>
            </svg>
          </span>
        </button>
      @endforeach
    </x-form>

    <div class="border-t border-hairline-soft px-3 py-[9px] text-xs text-muted">
      {{ __('Translations are community-maintained.') }}
    </div>
  </div>
</div>
