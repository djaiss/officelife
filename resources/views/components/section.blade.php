{{-- One area of a screen: a coloured tile saying what kind of thing it is, the title, and the card holding it. --}}
{{--
  A card whose whole content is a list takes padding="p-0", and its rows then
  reach the edges of the card rather than sitting in a second box inside it.
  Such a card has to clip its corners, since the rows are what draws them.

  @var string $title
  @var string $icon
  @var int $hue
  @var string|null $description
  @var string $padding
--}}
@props([
  'title',
  'icon',
  'hue' => 30,
  'description' => null,
  'padding' => 'p-5.5 sm:p-7',
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

  @isset($description)
    <p class="text-[15px] leading-relaxed text-body">{{ $description }}</p>
  @endisset

  <div @class(['rounded-[18px] bg-canvas ring-[1.5px] ring-hairline', 'overflow-hidden' => $padding === 'p-0', $padding])>
    {{ $slot }}
  </div>
</section>
