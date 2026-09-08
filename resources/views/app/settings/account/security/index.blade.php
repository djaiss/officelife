{{-- What somebody can change about the way they sign in: their password, the code asked for on top of it, and the keys that let a machine act as them. --}}
{{--
  @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel
--}}
<x-top-bar-layout :title="__('Security and access')">
  <x-slot:top-bar>
    <x-top-bar :company-name="$viewModel->companyName()" :name="$viewModel->name()" :employee="$viewModel->employee()" />
  </x-slot:top-bar>

  <nav class="mt-5.5 mb-6.5 flex items-center gap-2.25 text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
    <a href="{{ route('home.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Dashboard') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <a href="{{ route('settings.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Settings') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <span class="font-medium text-ink" aria-current="page">{{ __('Security and access') }}</span>
  </nav>

  <div class="mb-11">
    <h1 class="mb-2 text-4xl leading-tight font-bold tracking-tight text-ink">{{ __('Security and access') }}</h1>
    <p class="text-lg leading-normal text-pretty text-body">{{ __('How you sign in to your account, and what else may act as you.') }}</p>
  </div>

  <div class="space-y-10">
    <x-section :title="__('Password')" icon="password" :hue="150">
      <x-slot:help>
        <x-help :title="__('Password')">
          {{ __('We never store your password itself, only a hash of it, which is why we ask for the current one rather than showing it to you. Changing it is written to your logs, so an account you share by accident leaves a trace.') }}
        </x-help>
      </x-slot:help>

      @include('app.settings.account.security._change-password', ['viewModel' => $viewModel])
    </x-section>

    <x-section :title="__('Two factor authentication')" icon="two-factor" :hue="200">
      <x-slot:help>
        <x-help :title="__('Two factor authentication')">
          {{ __('A password can be guessed, reused or stolen. A code that changes every thirty seconds, on a phone in your pocket, cannot be any of those things from a distance. Turning this on means somebody who knows your password still cannot get in.') }}
        </x-help>
      </x-slot:help>

      @if ($viewModel->usesTwoFactorAuthentication())
        @include('app.settings.account.security._two-factor-on', ['viewModel' => $viewModel])
      @else
        @include('app.settings.account.security._two-factor-off')
      @endif
    </x-section>

    <x-section :title="__('API keys')" icon="api-keys" :hue="280">
      <x-slot:help>
        <x-help :title="__('API keys')" align="right">
          {{ __('A key is a password for machines. It is shown once, when you make it, and only a hash of it is kept afterwards, so a key you mislay is replaced rather than looked up. Revoking one stops it working straight away.') }}
        </x-help>
      </x-slot:help>

      @include('app.settings.account.security._api-keys', ['viewModel' => $viewModel])
    </x-section>
  </div>
</x-top-bar-layout>
