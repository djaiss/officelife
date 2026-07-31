{{--
  The short confirmation shown after something went right. It is announced on
  its own, rather than read as loose text, because it appears after the page
  has already been read.

  @var string|null $message
--}}
@props([
  'message' => null,
])

@if ($message)
  <div role="status" {{ $attributes->class(['rounded-lg border border-hairline bg-canvas px-4 py-3 text-[13px] text-ink']) }}>
    {{ $message }}
  </div>
@endif
