{{--
  The column listing every role of the company, with the line under each name
  saying how much it grants and how many people hold it.

  It sticks under the header of the layer on a wide screen, so moving between
  roles never means scrolling back up. Below lg there is no second column to sit
  beside, so it becomes the first band of the page instead.

  @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel
--}}
<div class="border-hairline-soft px-4 pt-5 pb-6 max-lg:border-b lg:sticky lg:top-13.5 lg:border-r lg:pb-15">
  <div class="flex items-center gap-2 px-1.5 pb-2.5">
    <h2 class="text-xs tracking-wide text-muted-soft uppercase">{{ $viewModel->rolesHeader() }}</h2>

    <button
      type="button"
      x-on:click="creating = true"
      aria-label="{{ __('Create a role') }}"
      class="ml-auto flex size-6 cursor-pointer items-center justify-center rounded-md border border-hairline bg-canvas text-body transition-colors hover:bg-hover hover:text-ink"
    >
      <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true">
        <line x1="8" y1="3.4" x2="8" y2="12.6"></line>
        <line x1="3.4" y1="8" x2="12.6" y2="8"></line>
      </svg>
    </button>
  </div>

  <div class="space-y-px">
    @foreach ($viewModel->list() as $entry)
      <a
        href="{{ $entry['url'] }}"
        @if ($entry['selected']) aria-current="page" @endif
        class="flex items-center gap-2.25 rounded-md px-2.5 py-2 {{ $entry['selected'] ? 'bg-hover' : 'hover:bg-hover' }}"
      >
        <span class="min-w-0">
          <span class="block truncate text-sm text-ink {{ $entry['selected'] ? 'font-semibold' : 'font-medium' }}">{{ $entry['name'] }}</span>

          <span class="mt-0.25 block truncate text-xs text-muted">{{ $entry['summary'] }}</span>
        </span>

        @unless ($entry['isEditable'])
          <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="ml-auto shrink-0 text-muted-soft" aria-hidden="true">
            <title>{{ __('Not editable') }}</title>
            <rect x="3.6" y="7" width="8.8" height="6.2" rx="1.4"></rect>
            <path d="M5.8 7V5.4a2.2 2.2 0 0 1 4.4 0V7"></path>
          </svg>
        @endunless
      </a>
    @endforeach
  </div>

  <x-notice class="mt-4">
    {{ __('Somebody may hold several roles at once. What they can do is everything their roles grant added together, so no role ever takes anything away.') }}
  </x-notice>
</div>
