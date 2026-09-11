{{-- The bar above the list: which of the three lists to read, what to search for, and the order to read it in. --}}
{{--
  The three lists are links rather than buttons, because each one is a path of
  its own that can be linked to and gone back to. The order is a link for the
  same reason, and says which order the list is in rather than what clicking it
  does, so the whole sentence is on it for anybody who cannot see the list.

  The search is a form with one field, so pressing enter is what runs it, and it
  keeps whichever list is being read by carrying it in the action.

  @var \App\ViewModels\Settings\Administration\OfficesViewModel $viewModel
--}}
@php
  $sort = $viewModel->sortToggle();
@endphp

<div class="mb-4 flex flex-wrap items-center gap-2.5">
  <div class="flex gap-0.75 rounded-xl bg-canvas p-1 ring-[1.5px] ring-hairline">
    @foreach ($viewModel->scopes() as $scope)
      <a
        href="{{ $scope['url'] }}"
        data-turbo="true"
        @if ($scope['current']) aria-current="page" @endif
        class="rounded-[9px] px-3.5 py-2 text-[15px] font-semibold transition-colors {{ $scope['current'] ? 'bg-nav-hover text-ink' : 'text-muted hover:text-ink' }}"
      >
        {{ $scope['label'] }}
      </a>
    @endforeach
  </div>

  <form method="get" action="{{ url()->current() }}" class="min-w-55 flex-1">
    <label for="q" class="sr-only">{{ __('Search the offices') }}</label>

    @foreach ($viewModel->sortState() as $field => $value)
      <input type="hidden" name="{{ $field }}" value="{{ $value }}" />
    @endforeach

    <input
      type="search"
      id="q"
      name="q"
      value="{{ $viewModel->search() }}"
      placeholder="{{ __('Search an office, a city or a country') }}"
      class="block w-full appearance-none rounded-xl border-[1.5px] border-hairline-strong bg-input px-3.5 py-2.5 text-base text-ink placeholder-placeholder transition-colors duration-150 hover:border-focus hover:bg-hover focus:border-focus focus:bg-canvas focus:ring-3 focus:ring-focus/15 focus:outline-none"
    />
  </form>

  <a
    href="{{ $sort['url'] }}"
    data-turbo="true"
    aria-label="{{ $sort['description'] }}"
    class="rounded-xl bg-canvas px-4 py-2.5 text-[15px] font-semibold whitespace-nowrap text-body ring-[1.5px] ring-hairline transition-colors hover:text-ink hover:ring-ink"
  >
    {{ $sort['label'] }}
  </a>
</div>
