{{--
  The label of a form field.

  @var string|null $value
--}}
@props([
  'value' => null,
])

<label {{ $attributes->class(['block text-sm leading-tight font-semibold text-ink']) }}>{{ $value ?? $slot }}</label>
