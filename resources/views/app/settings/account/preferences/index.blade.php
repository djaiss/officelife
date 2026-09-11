{{-- What somebody chose about the way the application reads to them. --}}
{{-- @var \App\ViewModels\Settings\Account\Preferences\PreferencesViewModel $viewModel --}}
<x-top-bar-layout :title="__('Preferences')">
  <x-slot:top-bar>
    <x-top-bar :company-name="$viewModel->companyName()" :name="$viewModel->name()" :employee="$viewModel->employee()" />
  </x-slot:top-bar>

  <!-- breadcrumb -->
  <nav class="mt-5.5 mb-6.5 flex items-center gap-2.25 text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
    <a href="{{ route('home.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Dashboard') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <a href="{{ route('settings.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Settings') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <span class="font-medium text-ink" aria-current="page">{{ __('Preferences') }}</span>
  </nav>

  <!-- page title -->
  <div class="mb-11">
    <h1 class="mb-2 text-4xl leading-tight font-bold tracking-tight text-ink">{{ __('Preferences') }}</h1>
    <p class="text-lg leading-normal text-pretty text-body">{{ __('How the application reads to you, on every device you sign in from.') }}</p>
  </div>

  <div class="space-y-4">
    <!-- language and clock -->
    <x-section :title="__('General')" icon="preferences" :hue="310">
      <x-slot:help>
        <x-help :title="__('General')">
          {{ __('These are yours alone. Changing them here changes nothing for your colleagues, and nothing about how your company is set up.') }}
        </x-help>
      </x-slot:help>

      <div class="grid gap-x-10 gap-y-3.5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
        <div class="min-w-0">
          <p class="font-semibold text-ink">{{ __('Language') }}</p>
          <p class="mt-1 text-[15px] leading-relaxed text-body">{{ __('The language the interface is drawn in.') }}</p>
        </div>

        <x-form method="put" :action="route('settings.preferences.update')" id="language-form">
          <input type="hidden" name="time_format" value="{{ $viewModel->timeFormat()->value }}" />

          <x-dropdown
            name="locale"
            :label="$viewModel->localeLabel()"
            :options="$viewModel->locales()"
            :title="__('Interface language')"
          />
        </x-form>
      </div>

      <div class="mt-6 grid gap-x-10 gap-y-3.5 border-t border-hairline-soft pt-6 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
        <div class="min-w-0">
          <p class="font-semibold text-ink">{{ __('Time format') }}</p>
          <p class="mt-1 text-[15px] leading-relaxed text-body">{{ __('How every clock time is written. Dates are unaffected.') }}</p>
        </div>

        <x-form
          method="put"
          :action="route('settings.preferences.update')"
          id="time-format-form"
          x-target="time-format-form language-form time-preview"
          class="transition-opacity [&[aria-busy]]:opacity-60"
        >
          <input type="hidden" name="locale" value="{{ $viewModel->locale() }}" />

          <x-dropdown
            name="time_format"
            :label="$viewModel->timeFormatLabel()"
            :options="$viewModel->timeFormats()"
            :title="__('Time format')"
            monospaced-hints
          />
        </x-form>
      </div>
    </x-section>

    <p id="time-preview" class="text-sm text-muted-soft">
      {{ __('It is :time where you are, in :language.', ['time' => $viewModel->timePreview(), 'language' => $viewModel->localeLabel()]) }}
    </p>
  </div>
</x-top-bar-layout>
