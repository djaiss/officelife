{{--
  The primary button. It renders an anchor when it is given an href, and a button
  otherwise. The icon slot sits before the label.

  @var string|null $href
  @var string $type
--}}
@props([
  'href',
  'type' => 'submit',
])

@php
  $classes = 'relative inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] py-3 text-[13.5px] font-semibold whitespace-nowrap transition-colors duration-150 bg-[var(--color-accent)] text-[var(--color-accent-foreground)] hover:bg-[color-mix(in_oklab,_var(--color-accent),_transparent_12%)] focus-visible:ring-2 focus-visible:ring-focus focus-visible:outline-none disabled:pointer-events-none disabled:bg-disabled disabled:text-on-disabled [:where(&)]:px-5';
@endphp

@isset($href)
  <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @isset($icon)
      <span class="shrink-0">{{ $icon }}</span>
    @endisset

    {{ $slot }}
  </a>
@else
  <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    @isset($icon)
      <span class="shrink-0">{{ $icon }}</span>
    @endisset

    {{ $slot }}
  </button>
@endif
