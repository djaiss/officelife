{{-- The matrix: everything a role can be allowed to do, whether this one is, and over whom. --}}
{{--
  A permission that covers the whole company has nothing to narrow down, so its
  row says so instead of offering a toggle that would mean nothing. The rest
  carry one scope each, shown as the sentence it stands for and flipped by
  clicking it.

  Filtering only hides rows. The fields are still in the page and still
  submitted, so a search left in the box can never quietly drop a grant on the
  way to saving.

  The bar beside a group title describes what is saved, not what is on screen: it
  comes back right once the form has been through the server.

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

  $bars = [
    'none' => 'bg-error',
    'partial' => 'bg-warning',
    'full' => 'bg-success',
  ];
@endphp

<div
  x-data="{
    query: '',
    collapsed: {},
    search: @js($search),
    sections: @js($sections),
    term() { return this.query.trim().toLowerCase() },
    matches(value) { return this.term() === '' || this.search[value].includes(this.term()) },
    sectionMatches(title) { return this.sections[title].some((value) => this.matches(value)) },
    open(title) { return this.term() !== '' || ! this.collapsed[title] },
    nothingMatches() { return ! Object.keys(this.search).some((value) => this.matches(value)) },
  }"
>
  <div class="mb-3 flex flex-wrap items-end justify-between gap-x-4 gap-y-1">
    <h2 class="text-[22px] leading-tight font-bold tracking-tight text-ink">{{ __('Allowed to') }}</h2>

    <p class="text-[15px] text-muted">{{ $viewModel->grantCountLabel() }}</p>
  </div>

  {{--
    The filter is not part of the role, so what is typed into it must not reach
    the form around it: the event is stopped here rather than counted as a
    change, and enter filters instead of saving.
  --}}
  <label for="permission-filter" class="sr-only">{{ __('Filter permissions') }}</label>

  <input
    type="search"
    id="permission-filter"
    x-model="query"
    x-on:input.stop
    x-on:keydown.enter.prevent
    placeholder="{{ __('Filter permissions') }}"
    class="mb-3 block w-full appearance-none rounded-xl border-[1.5px] border-hairline-strong bg-input px-3.5 py-2.5 text-base text-ink placeholder-placeholder transition-colors duration-150 hover:border-focus hover:bg-hover focus:border-focus focus:bg-canvas focus:ring-3 focus:ring-focus/15 focus:outline-none"
  />

  <div class="rounded-[18px] bg-canvas px-2.5 py-2 ring-[1.5px] ring-hairline">
    @foreach ($groups as $group)
      <div x-show="sectionMatches(@js($group['title']))">
        <button
          type="button"
          x-on:click="collapsed[@js($group['title'])] = ! collapsed[@js($group['title'])]"
          :aria-expanded="open(@js($group['title'])) ? 'true' : 'false'"
          class="flex w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-3 text-left transition-colors hover:bg-hover"
        >
          <svg
            width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
            class="shrink-0 text-muted transition-transform"
            :class="open(@js($group['title'])) ? '' : '-rotate-90'"
            aria-hidden="true"
          >
            <path d="M4.4 6.4 8 10l3.6-3.6"></path>
          </svg>

          <span class="text-[13px] font-bold tracking-[0.08em] whitespace-nowrap text-body uppercase">{{ $group['title'] }}</span>

          <span class="hidden truncate text-[13px] text-muted-soft lg:block">{{ $group['note'] }}</span>

          <span class="ml-auto block h-1.5 w-16 shrink-0 overflow-hidden rounded-full bg-sunken" role="img" aria-label="{{ $group['count'] }}" title="{{ $group['count'] }}">
            <span class="block h-full rounded-full {{ $bars[$group['tone']] }}" style="width: {{ $group['width'] }}"></span>
          </span>
        </button>

        @foreach ($group['permissions'] as $permission)
          <div
            x-data="{ granted: @js($permission['granted']), scope: @js($permission['scope']), scopes: @js($permission['scopes']) }"
            x-show="open(@js($group['title'])) && matches(@js($permission['value']))"
            class="grid gap-x-3.5 gap-y-1.5 rounded-xl py-2.5 pr-3 pl-8.5 transition-colors hover:bg-hover sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
          >
            <label class="flex min-w-0 cursor-pointer items-center gap-3.5">
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
                class="flex size-5.5 shrink-0 items-center justify-center rounded-[7px] bg-canvas text-transparent ring-[1.5px] ring-hairline-strong transition-colors peer-checked:bg-ink peer-checked:text-canvas peer-checked:ring-ink peer-focus-visible:ring-2 peer-focus-visible:ring-focus"
              >
                <svg width="11" height="11" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path>
                </svg>
              </span>

              <span class="min-w-0 text-base leading-snug font-medium text-ink">{{ $permission['label'] }}</span>
            </label>

            @if ($permission['targetsEmployee'])
              <button
                type="button"
                x-cloak
                x-show="granted"
                x-on:click="scope = scope === 'self' ? 'company' : 'self'"
                x-text="scopes[scope]"
                @disabled(! $role['isEditable'])
                aria-label="{{ __('Scope of :permission', ['permission' => $permission['label']]) }}"
                class="cursor-pointer rounded-md text-sm font-semibold whitespace-nowrap text-muted underline decoration-hairline-strong underline-offset-4 transition-colors hover:text-ink focus-visible:ring-2 focus-visible:ring-focus focus-visible:outline-none disabled:cursor-not-allowed max-sm:justify-self-start max-sm:pl-9"
              ></button>

              <input type="hidden" name="permissions[{{ $permission['value'] }}][scope]" :value="scope" />
            @else
              <p x-cloak x-show="granted" class="text-sm font-semibold whitespace-nowrap text-muted-soft max-sm:pl-9">{{ __('Everybody in the company, always') }}</p>
            @endif
          </div>
        @endforeach
      </div>
    @endforeach

    <p x-cloak x-show="nothingMatches()" class="px-3 py-6 text-center text-[15px] text-muted">{{ __('No permission matches that.') }}</p>
  </div>

  <p class="mt-3 text-sm leading-relaxed text-pretty text-muted">
    {{ __('The words on the right say who a permission reaches. Click them to switch. A few permissions cannot be narrowed and always cover the whole company.') }}
  </p>
</div>
