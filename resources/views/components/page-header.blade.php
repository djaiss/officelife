{{--
  The title of a screen and the line under it that says what the screen is for.
  Both can be given as an attribute or as a named slot.

  @var string|null $title
  @var string|null $description
--}}
@props([
  'title' => null,
  'description' => null,
])

<div {{ $attributes->class(['space-y-[6px]']) }}>
  <h1 class="text-2xl font-semibold tracking-[-0.02em] text-ink">{{ $title ?? $slot }}</h1>

  @isset($description)
    <p class="text-[13.5px] text-body">{{ $description }}</p>
  @endisset
</div>
