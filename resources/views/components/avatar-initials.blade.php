{{--
  The initials of somebody, in a circle, for when there is no avatar to show.

  @var string $name
  @var int $size
  @var string $tone
--}}
@props([
  'name',
  'size' => 34,
  'tone' => 'neutral',
])

@php
  $initials = collect(preg_split('/\s+/', trim($name)))
    ->filter()
    ->take(2)
    ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
    ->implode('');

  $tones = [
    'neutral' => 'rounded-full border border-hairline-soft bg-card text-body',
    'accent' => 'accent-tile rounded-[9px]',
  ];
@endphp

<span
  {{ $attributes->class(['flex shrink-0 items-center justify-center font-semibold', $tones[$tone]]) }}
  style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.35) }}px;"
  aria-hidden="true"
>{{ $initials }}</span>
