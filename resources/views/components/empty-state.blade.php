{{-- What a list shows when it holds nothing. --}}
{{--
  @var string|null $title
  @var string|null $href
  @var string|null $linkLabel
--}}
@props([
  'title' => null,
  'href' => null,
  'linkLabel' => null,
])

<div {{ $attributes->class(['flex min-h-65 flex-col items-center justify-center gap-3.5 px-8 py-11 text-center']) }}>
  @isset($icon)
    <span class="flex size-13 items-center justify-center rounded-full border border-hairline bg-sunken text-placeholder" aria-hidden="true">
      {{ $icon }}
    </span>
  @endisset

  <div class="max-w-90 space-y-1.5">
    @isset($title)
      <p class="text-sm font-semibold text-ink">{{ $title }}</p>
    @endisset

    <p class="text-sm leading-relaxed text-muted">{{ $slot }}</p>
  </div>

  @if ($href && $linkLabel)
    <x-link :href="$href" class="text-sm text-brand decoration-brand/25">{{ $linkLabel }}</x-link>
  @endif
</div>
