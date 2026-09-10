{{--
  The button that goes through with something irreversible: delete, revoke, archive,
  turn off. It renders an anchor when it is given an href, and a button otherwise. The
  icon slot sits before the label.

  It is the primary button in a different colour, so the two are the same height and
  the same shape side by side. Being a component rather than a class on <x-button>, it
  no longer depends on `bg-error` and `bg-accent` landing in the right order in the
  stylesheet, which is what decided the colour when the two were written together.

  @var string|null $href
  @var string $type
--}}
@props([
  'href',
  'type' => 'submit',
])

@php
  $classes = 'relative inline-flex cursor-pointer items-center justify-center gap-2 rounded-[10px] border-[1.5px] border-transparent py-2.5 text-[15px] font-semibold whitespace-nowrap transition-colors duration-150 bg-error text-accent-foreground hover:bg-error/88 focus-visible:ring-2 focus-visible:ring-focus focus-visible:outline-none disabled:pointer-events-none disabled:bg-disabled disabled:text-on-disabled [:where(&)]:px-5';
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
