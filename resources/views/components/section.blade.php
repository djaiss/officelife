{{-- One area of a screen: a coloured tile saying what kind of thing it is, the title, and the card holding it. --}}
{{--
  @var string $title
  @var string $icon
  @var int $hue
--}}
@props([
  'title',
  'icon',
  'hue' => 30,
])

<section {{ $attributes->class(['space-y-3.5']) }}>
  <div class="flex items-center gap-3">
    <span class="accent-tile grid size-8.5 shrink-0 place-items-center rounded-xl" style="--tile-hue: {{ $hue }}">
      <x-settings-icon :name="$icon" />
    </span>

    <h2 class="text-[22px] font-bold tracking-tight text-ink">{{ $title }}</h2>

    @isset($help)
      {{ $help }}
    @endisset
  </div>

  <div class="rounded-[18px] bg-canvas p-5.5 ring-[1.5px] ring-hairline sm:p-7">
    {{ $slot }}
  </div>
</section>
