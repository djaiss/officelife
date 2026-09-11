{{-- One row of a list drawn inside a <x-box>. --}}
{{-- @var string $padding --}}
@props([
  'padding' => 'px-3 py-3',
])

<div {{ $attributes->class(['border-b border-hairline-soft transition-colors duration-150 first:rounded-t-xl last:rounded-b-xl last:border-b-0 hover:bg-hover', $padding]) }}>
  {{ $slot }}
</div>
