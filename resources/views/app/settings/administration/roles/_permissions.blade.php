{{--
  The matrix: everything a role can be allowed to do, whether this one is, and
  over whom.

  A permission that covers the whole company has nothing to narrow down, so its
  row says so instead of offering buttons that would mean nothing. The rest carry
  one scope each, and the row keeps them out of reach until the permission itself
  is ticked.

  Filtering only hides rows. The fields are still in the page and still submitted,
  so a search left in the box can never quietly drop a grant on the way to saving.

  The counts beside each group describe what is saved, not what is on screen: they
  come back right once the form has been through the server.

  @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel
  @var array $role
--}}
@php
  $groups = $viewModel->groups();

  /* What a row is matched against, and which rows belong to which group, so the
     filter can hide a whole section once nothing under it matches. */
  $search = [];
  $sections = [];

  foreach ($groups as $group) {
    $sections[$group['title']] = array_column($group['permissions'], 'value');

    foreach ($group['permissions'] as $permission) {
      $search[$permission['value']] = mb_strtolower($permission['value'].' '.$permission['label']);
    }
  }
@endphp

<div class="mt-6 flex flex-wrap items-end gap-x-4 gap-y-1">
  <div class="space-y-0.75">
    <h2 class="text-sm font-semibold tracking-tight text-ink">{{ __('Permissions') }}</h2>

    <p class="text-xs text-muted">{{ __('Every permission granted carries one scope, which decides the people it applies to.') }}</p>
  </div>

  <p class="ml-auto text-xs text-muted">{{ $viewModel->grantCountLabel() }}</p>
</div>

<div
  x-data="{
    query: '',
    collapsed: {},
    search: @js($search),
    sections: @js($sections),
    term() { return this.query.trim().toLowerCase() },
    matches(value) { return this.term() === '' || this.search[value].includes(this.term()) },
    matchCount() { return Object.keys(this.search).filter((value) => this.matches(value)).length },
    sectionMatches(title) { return this.sections[title].some((value) => this.matches(value)) },
    open(title) { return this.term() !== '' || ! this.collapsed[title] },
    allCollapsed() { return Object.values(this.collapsed).filter(Boolean).length === Object.keys(this.sections).length },
    toggleAll() { this.collapsed = this.allCollapsed() ? {} : Object.fromEntries(Object.keys(this.sections).map((title) => [title, true])) },
  }"
  class="mt-2.75 overflow-hidden rounded-xl border border-hairline bg-canvas"
>
  <div class="flex items-center gap-2.5 border-b border-hairline-soft bg-sunken py-2.25 pr-3 pl-3.5">
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" class="shrink-0 text-muted-soft" aria-hidden="true">
      <circle cx="7.2" cy="7.2" r="4.2"></circle>
      <line x1="10.4" y1="10.4" x2="13.4" y2="13.4"></line>
    </svg>

    {{--
      The filter is not part of the role, so what is typed into it must not reach
      the form around it: the event is stopped here rather than counted as a
      change, and enter filters instead of saving.
    --}}
    <input
      type="search"
      x-model="query"
      x-on:input.stop
      x-on:keydown.enter.prevent
      aria-label="{{ __('Filter permissions') }}"
      placeholder="{{ __('Filter permissions') }}"
      class="min-w-0 flex-1 bg-transparent text-sm text-ink placeholder-placeholder outline-none"
    />

    <p x-cloak x-show="term() !== ''" class="text-xs whitespace-nowrap text-muted" x-text="matchCount() === 1 ? @js(__('1 match')) : @js(__(':count matches')).replace(':count', matchCount())"></p>

    <button
      type="button"
      x-show="term() === ''"
      x-on:click="toggleAll()"
      x-text="allCollapsed() ? @js(__('Expand all')) : @js(__('Collapse all'))"
      class="shrink-0 cursor-pointer rounded-md border border-hairline bg-canvas px-2 py-0.75 text-xs whitespace-nowrap text-body transition-colors hover:bg-hover"
    ></button>

    <button
      type="button"
      x-cloak
      x-show="term() !== ''"
      x-on:click="query = ''"
      class="shrink-0 cursor-pointer rounded-md border border-hairline bg-canvas px-2 py-0.75 text-xs text-body transition-colors hover:bg-hover"
    >{{ __('Clear') }}</button>
  </div>

  @foreach ($groups as $group)
    <div x-show="sectionMatches(@js($group['title']))">
      <button
        type="button"
        x-on:click="collapsed[@js($group['title'])] = ! collapsed[@js($group['title'])]"
        :aria-expanded="open(@js($group['title'])) ? 'true' : 'false'"
        class="flex w-full cursor-pointer items-center gap-2 border-b border-hairline-soft bg-sunken px-3.5 py-2.5 text-left transition-colors hover:bg-hover"
      >
        <svg
          width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
          class="shrink-0 text-muted transition-transform"
          :class="open(@js($group['title'])) ? '' : '-rotate-90'"
          aria-hidden="true"
        >
          <path d="M4.4 6.4 8 10l3.6-3.6"></path>
        </svg>

        <span class="text-xs font-semibold text-body">{{ $group['title'] }}</span>

        <span class="hidden text-xs text-muted-soft sm:block">{{ $group['note'] }}</span>

        <span class="ml-auto shrink-0 text-xs text-muted-soft">{{ $group['count'] }}</span>
      </button>

      @foreach ($group['permissions'] as $permission)
        <div
          x-data="{ granted: @js($permission['granted']) }"
          x-show="open(@js($group['title'])) && matches(@js($permission['value']))"
          class="grid items-center gap-x-4 gap-y-3 border-b border-hairline-soft px-3.5 py-3 last:border-b-0 sm:grid-cols-[minmax(0,1fr)_minmax(0,300px)]"
        >
          <label class="flex min-w-0 cursor-pointer items-start gap-2.75">
            <input
              type="checkbox"
              name="permissions[{{ $permission['value'] }}][granted]"
              value="1"
              x-model="granted"
              @checked($permission['granted'])
              @disabled(! $role['isEditable'])
              class="peer sr-only"
            />

            <span
              aria-hidden="true"
              class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-sm border-2 border-hairline-strong bg-canvas text-transparent transition-colors peer-checked:border-primary peer-checked:bg-primary peer-checked:text-on-primary peer-focus-visible:ring-2 peer-focus-visible:ring-focus peer-disabled:border-disabled"
            >
              <svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path>
              </svg>
            </span>

            <span class="min-w-0">
              <span class="block text-sm text-ink">{{ $permission['label'] }}</span>

              <span class="mt-0.25 block font-mono text-xs text-muted">{{ $permission['value'] }}</span>
            </span>
          </label>

          @if ($permission['targetsEmployee'])
            <fieldset class="grid grid-cols-2 gap-0.75 rounded-lg border border-hairline bg-sunken p-0.75 transition-opacity" :class="granted ? '' : 'opacity-55'">
              <legend class="sr-only">{{ __('Scope of :permission', ['permission' => $permission['label']]) }}</legend>

              @foreach ($permission['scopes'] as $scope)
                <label class="block">
                  <input
                    type="radio"
                    name="permissions[{{ $permission['value'] }}][scope]"
                    value="{{ $scope['value'] }}"
                    @checked($permission['scope'] === $scope['value'])
                    @if ($role['isEditable']) :disabled="! granted" @else disabled @endif
                    class="peer sr-only"
                  />

                  <span class="block cursor-pointer rounded-md px-2 py-1.25 text-center text-xs text-body transition-colors peer-checked:bg-canvas peer-checked:font-semibold peer-checked:text-ink peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-focus peer-disabled:cursor-not-allowed">
                    {{ $scope['label'] }}
                  </span>
                </label>
              @endforeach
            </fieldset>
          @else
            <p class="text-xs text-muted-soft">{{ __('Always company wide') }}</p>
          @endif
        </div>
      @endforeach
    </div>
  @endforeach
</div>

<p class="mt-2.25 text-xs leading-relaxed text-muted-soft">
  @foreach ($viewModel->scopeLegend() as $scope)
    <span class="text-body">{{ $scope['short'] }}</span>: {{ $scope['label'] }}@unless ($loop->last) · @endunless
  @endforeach
</p>
