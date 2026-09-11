{{-- Setting up two factor authentication, one step at a time. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\TwoFactorEnrolmentViewModel $viewModel --}}
<x-top-bar-layout :title="__('Two factor authentication')">
  <x-slot:top-bar>
    <x-top-bar :company-name="$viewModel->companyName()" :name="$viewModel->name()" :employee="$viewModel->employee()" />
  </x-slot:top-bar>

  <nav class="mt-5.5 mb-6.5 flex items-center gap-2.25 text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
    <a href="{{ route('home.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Dashboard') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <a href="{{ route('settings.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Settings') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <a href="{{ route('settings.security.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Security and access') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <span class="font-medium text-ink" aria-current="page">{{ __('Two factor authentication') }}</span>
  </nav>

  <div class="mb-11">
    <h1 class="mb-2 text-4xl leading-tight font-bold tracking-tight text-ink">{{ __('Turn on two factor authentication') }}</h1>
    <p class="text-lg leading-normal text-pretty text-body">{{ __('Two steps, and you are done.') }}</p>
  </div>

  <div class="space-y-10">
    <x-section :title="__('Pair your authenticator app')" icon="two-factor" :hue="200">
      <div class="grid gap-10 md:grid-cols-[minmax(0,1fr)_auto]">
        <div class="space-y-2.5 text-[15px] leading-relaxed text-body">
          <p>{{ __('Open the authenticator app on your phone, add an account, and point its camera at this square.') }}</p>
          <p>{{ __('It files the account under :email.', ['email' => $viewModel->email()]) }}</p>

          <div class="space-y-1.5 pt-1.5">
            <p class="text-sm text-muted">{{ __('No camera? Type this into the app instead.') }}</p>

            <p class="rounded-md border border-hairline bg-sunken px-3 py-2 font-mono text-sm tracking-wider break-all text-ink select-all">{{ $viewModel->secret() }}</p>
          </div>
        </div>

        <div class="mx-auto rounded-xl border border-hairline bg-white p-3">
          {{ $viewModel->qrCode() }}
        </div>
      </div>
    </x-section>

    <x-section :title="__('Prove it worked')" icon="password" :hue="150">
      <div class="grid gap-10 md:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
        <div class="space-y-2.5 text-[15px] leading-relaxed text-body">
          <p>{{ __('Type the six digits the app is showing right now. They change every thirty seconds.') }}</p>
          <p>{{ __('Nothing changes about your account until this code is accepted, so you cannot lock yourself out here.') }}</p>
        </div>

        <x-form method="post" :action="route('settings.twoFactor.create')" class="space-y-5">
          <x-input
            id="code"
            :label="__('Six digit code')"
            placeholder="123456"
            autocomplete="one-time-code"
            inputmode="numeric"
            :error="$errors->get('code')"
            required
            autofocus
          />

          <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <x-button.secondary :href="route('settings.security.index')" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

            <x-button>{{ __('Turn it on') }}</x-button>
          </div>
        </x-form>
      </div>
    </x-section>
  </div>
</x-top-bar-layout>
