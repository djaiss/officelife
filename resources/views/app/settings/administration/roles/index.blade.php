{{--
  The screen where the roles of a company are looked after: every role down the
  left, and the one being read on the right, with what it is allowed to do and
  who holds it.

  It is big enough that it takes the window rather than a panel in the middle of
  the settings, which is what the stacked layer around it is for.

  `dirty` and `creating` are declared on the layout tag, so they are in scope for
  the whole screen: the save bar the layout draws in its sticky header watches a
  form further down, and the button that opens the new role dialog sits in one
  column while the dialog itself is the last thing on the page. The save bar and
  the form it saves are not nested, so the button reaches the form through the
  html `form` attribute rather than by sitting inside it.

  The name is edited in place, as a field dressed as the heading it replaces. It
  lives outside the form and points back at it, since the buttons beside it are
  forms of their own and a form cannot hold another.

  @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel
--}}
@php
  $role = $viewModel->role();
@endphp

<x-stacked-layer-layout
  :title="__('Roles and permissions')"
  :back-title="__('Settings')"
  :back-url="route('settings.profile.index')"
  x-data="{ dirty: false, creating: {{ $errors->createRole->any() ? 'true' : 'false' }} }"
>
  <x-slot:header>
    <div class="flex min-w-0 items-center gap-2.25">
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="shrink-0 text-ink" aria-hidden="true">
        <path d="M8 2.2 13.2 4v4c0 3-2.2 5-5.2 5.8C5 13 2.8 11 2.8 8V4L8 2.2Z"></path>
        <path d="M5.9 8.1 7.4 9.6l2.8-3"></path>
      </svg>

      <h1 class="truncate text-sm font-semibold tracking-tight text-ink">{{ __('Roles and permissions') }}</h1>

      <p class="hidden border-l border-hairline pl-2.25 text-xs whitespace-nowrap text-muted-soft lg:block">
        {{ __('Level 2 of :screen', ['screen' => __('Settings')]) }}
      </p>
    </div>
  </x-slot:header>

  <x-slot:actions>
    @if ($role && $role['isEditable'])
      <div x-cloak x-show="dirty" class="flex items-center gap-2.5">
        <p class="hidden text-xs text-muted sm:block">{{ __('Unsaved changes') }}</p>

        <x-button.secondary :href="route('settings.roles.show', $role['id'])" class="py-1.5 text-xs [:where(&)]:px-2.75">
          {{ __('Discard') }}
        </x-button.secondary>

        <x-button form="role-form" class="py-1.5 text-xs [:where(&)]:px-3.25">{{ __('Save role') }}</x-button>
      </div>
    @endif
  </x-slot:actions>

  <div class="grid items-start lg:grid-cols-[276px_minmax(0,1fr)]">
    @include('app.settings.administration.roles._roles', ['viewModel' => $viewModel])

    <div class="min-w-0 px-5 pt-6 pb-22 sm:px-7">
      @if ($role === null)
        <x-empty-state :title="__('This company has no roles left')">
          <x-slot:icon>
            <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4">
              <path d="M8 2.2 13.2 4v4c0 3-2.2 5-5.2 5.8C5 13 2.8 11 2.8 8V4L8 2.2Z"></path>
            </svg>
          </x-slot:icon>

          {{ __('Nobody can be given anything until there is a role to give. Make one to start handing out permissions again.') }}
        </x-empty-state>
      @else
        <div class="flex flex-wrap items-start gap-x-4 gap-y-3">
          <div class="min-w-0 flex-1 space-y-1.5">
            <div class="flex flex-wrap items-center gap-2.5">
              <label for="role-name" class="sr-only">{{ __('Name of the role') }}</label>

              <input
                type="text"
                id="role-name"
                name="name"
                form="role-form"
                value="{{ $role['name'] }}"
                maxlength="255"
                required
                @disabled(! $role['isEditable'])
                @if ($errors->has('name')) aria-invalid="true" aria-describedby="role-name-error" @endif
                class="-mx-2 max-w-full min-w-0 rounded-md border border-transparent bg-transparent px-2 py-0.5 text-2xl font-semibold tracking-tight text-ink transition-colors outline-none field-sizing-content hover:border-hairline focus:border-focus focus:bg-canvas disabled:hover:border-transparent"
              />

              <span class="rounded-full bg-hover px-2.25 py-0.75 font-mono text-xs text-body">{{ $role['slug'] }}</span>

              @unless ($role['isEditable'])
                <span class="rounded-full bg-warning/12 px-2.25 py-0.75 text-xs text-warning">{{ __('Not editable') }}</span>
              @endunless
            </div>

            <x-error :id="$errors->has('name') ? 'role-name-error' : null" :messages="$errors->get('name')" />
          </div>

          @include('app.settings.administration.roles._role-menu', ['viewModel' => $viewModel, 'role' => $role])
        </div>

        @if ($viewModel->warnsAboutAdministration())
          <div class="mt-4.5 flex items-start gap-2.5 rounded-lg border border-error/25 bg-error/8 px-3.5 py-3">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="mt-0.5 shrink-0 text-error" aria-hidden="true">
              <path d="M8 2.6 14.2 13H1.8L8 2.6Z"></path>
              <line x1="8" y1="6.6" x2="8" y2="9.4"></line>
              <circle cx="8" cy="11.2" r="0.5" fill="currentColor"></circle>
            </svg>

            <p class="text-xs leading-relaxed text-error">
              {{ __('This role administers the company. Anybody holding it can grant themselves every other permission, so treat it as full access.') }}
            </p>
          </div>
        @endif

        <x-form
          method="put"
          :action="$role['updateUrl']"
          id="role-form"
          x-on:input="dirty = true"
          x-on:change="dirty = true"
        >
          @include('app.settings.administration.roles._permissions', ['viewModel' => $viewModel, 'role' => $role])
        </x-form>

        @include('app.settings.administration.roles._people', ['viewModel' => $viewModel, 'role' => $role])
      @endif
    </div>
  </div>

  @include('app.settings.administration.roles._create-role', ['viewModel' => $viewModel])
</x-stacked-layer-layout>
