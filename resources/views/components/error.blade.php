{{-- The validation messages of a field. --}}
{{-- @var string|array<int, string>|null $messages --}}
@props([
  'messages',
])

@if ($messages)
  <ul {{ $attributes->merge(['class' => 'space-y-1 text-xs text-error']) }}>
    @foreach ((array) $messages as $message)
      <li>{{ $message }}</li>
    @endforeach
  </ul>
@endif
