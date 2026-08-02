{{--
  The four counts above the table. They describe the company rather than the list
  under them, so narrowing the search leaves them where they are.

  @var \App\ViewModels\Settings\Administration\LocationsViewModel $viewModel
--}}
<div id="locations-stats" class="grid grid-cols-2 gap-3 transition-opacity lg:grid-cols-4 [&[aria-busy]]:opacity-60">
  @foreach ($viewModel->stats() as $stat)
    <div class="rounded-xl border border-hairline bg-canvas px-4 py-3.25">
      <p class="text-xs tracking-wide text-muted-soft uppercase">{{ $stat['label'] }}</p>

      <p class="mt-1.5 text-xl font-semibold tracking-tight text-ink tabular-nums">{{ $stat['value'] }}</p>

      <p class="mt-0.5 text-xs text-muted">{{ $stat['note'] }}</p>
    </div>
  @endforeach
</div>
