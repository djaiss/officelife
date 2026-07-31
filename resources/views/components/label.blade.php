{{--
  The label of a form field.

  @var string|null $value
--}}
@props([
  'value' => null,
])

<label {{ $attributes->class(['block text-[12.5px] leading-tight font-semibold text-ink']) }}>{{ $value ?? $slot }}</label>
