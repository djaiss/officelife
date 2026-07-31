{{--
  A titled panel. The title, the description and the additional information can be
  given either as an attribute or as a named slot. Attributes land on the panel
  itself, not on the wrapper, so <x-box class="text-center"> styles the content.

  @var string|null $title
  @var string|null $description
  @var string|null $additionalInfo
  @var string $padding
--}}
@props([
  'title' => null,
  'padding' => 'p-[22px]',
  'description' => null,
  'additionalInfo' => null,
])

<div class="space-y-2">
  @isset($title)
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-ink">{{ $title }}</h2>

      @isset($actions)
        <div>{{ $actions }}</div>
      @endisset
    </div>
  @endisset

  @isset($description)
    <div class="space-y-2 text-sm text-muted">
      {{ $description }}
    </div>
  @endisset

  @isset($additionalInfo)
    {{ $additionalInfo }}
  @endisset

  <div {{ $attributes->merge(['class' => 'rounded-xl border border-hairline bg-canvas ' . $padding]) }}>
    {{ $slot }}
  </div>
</div>
