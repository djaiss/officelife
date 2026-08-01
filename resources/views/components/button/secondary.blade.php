{{--
  The neutral button, for the action that sits next to the primary one.

  @var string|null $href
  @var string $type
--}}
@props([
  'href',
  'type' => 'submit',
])

@php
  $classes = 'relative inline-flex cursor-pointer items-center justify-center gap-2 rounded-md border border-hairline-strong bg-canvas py-2.5 text-sm font-medium whitespace-nowrap text-ink transition-colors duration-150 hover:bg-hover focus-visible:ring-2 focus-visible:ring-focus focus-visible:outline-none disabled:pointer-events-none disabled:opacity-60 [:where(&)]:px-4';
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
