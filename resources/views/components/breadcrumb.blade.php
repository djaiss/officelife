{{-- The path from the dashboard down to the screen somebody is on, whose own step carries no link. --}}
{{-- @var array<string, string|null> $trail --}}
@props(['trail'])

<nav class="mt-5.5 mb-6.5 flex flex-wrap items-center gap-2.25 text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
  @foreach ($trail as $label => $url)
    @if (! $loop->first)
      <span class="text-muted-soft" aria-hidden="true">/</span>
    @endif

    @if ($url === null)
      <span class="font-medium text-ink" aria-current="page">{{ $label }}</span>
    @else
      <a href="{{ $url }}" data-turbo="true" class="transition-colors hover:text-ink">{{ $label }}</a>
    @endif
  @endforeach
</nav>
