{{--
  The bar above the table: what to search for, and which of the three lists to
  search in.

  The three lists are links rather than buttons, because each one is a path of
  its own that can be linked to and gone back to. The search is a form with one
  field, so pressing enter is what runs it, and it keeps whichever list is being
  read by carrying it in the action.

  @var \App\ViewModels\Settings\Administration\LocationsViewModel $viewModel
--}}
<div class="flex flex-wrap items-center gap-2">
  <form method="get" action="{{ url()->current() }}" class="min-w-60 flex-1">
    <label for="q" class="sr-only">{{ __('Search the offices') }}</label>

    @foreach ($viewModel->sortState() as $field => $value)
      <input type="hidden" name="{{ $field }}" value="{{ $value }}" />
    @endforeach

    <div class="flex items-center gap-2.25 rounded-md border border-hairline-strong bg-input px-3 py-2 transition-colors focus-within:border-focus focus-within:bg-canvas">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" class="shrink-0 text-muted-soft" aria-hidden="true">
        <circle cx="7.2" cy="7.2" r="4.2"></circle>
        <line x1="10.4" y1="10.4" x2="13.4" y2="13.4"></line>
      </svg>

      <input
        type="search"
        id="q"
        name="q"
        value="{{ $viewModel->search() }}"
        placeholder="{{ __('Search an office, a city or a country') }}"
        class="min-w-0 flex-1 border-none bg-transparent p-0 text-base text-ink placeholder-placeholder focus:outline-none sm:text-sm"
      />
    </div>
  </form>

  <div class="flex gap-0.5 rounded-md border border-hairline bg-sunken p-0.75">
    @foreach ($viewModel->scopes() as $scope)
      <a
        href="{{ $scope['url'] }}"
        data-turbo="true"
        @if ($scope['current']) aria-current="page" @endif
        class="rounded-sm px-2.75 py-1.25 text-xs transition-colors {{ $scope['current'] ? 'bg-canvas font-semibold text-ink shadow-sm' : 'text-body hover:text-ink' }}"
      >
        {{ $scope['label'] }}
      </a>
    @endforeach
  </div>
</div>
