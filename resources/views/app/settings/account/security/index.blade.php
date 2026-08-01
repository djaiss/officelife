{{--
  What somebody can change about the way they sign in: their password, and
  whether a code is asked for on top of it.

  Each panel holds a section of its own, kept in a partial beside this file, so
  what this screen is made of can be read in one go.

  @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel
--}}
<x-app-layout :title="__('Security')">
  <x-slot:sidebar>
    <x-settings.sidebar :company-name="$viewModel->companyName()" :name="$viewModel->name()" :employee="$viewModel->employee()" current="security" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <nav class="text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
      {{ __('Settings') }}
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <span class="text-ink" aria-current="page">{{ __('Security') }}</span>
    </nav>
  </x-slot:breadcrumb>

  <x-page-header
    :title="__('Security')"
    :description="__('How you sign in to your account.')"
  />

  <x-box :title="__('Change password')">
    <x-slot:help>
      <x-help :title="__('Change password')">
        {{ __('We never store your password itself, only a hash of it, which is why we ask for the current one rather than showing it to you. Changing it is written to your logs, so an account you share by accident leaves a trace.') }}
      </x-help>
    </x-slot:help>

    @include('app.settings.account.security._change-password')
  </x-box>

  <x-box :title="__('Two factor authentication')">
    <x-slot:help>
      <x-help :title="__('Two factor authentication')">
        {{ __('A password can be guessed, reused or stolen. A code that changes every thirty seconds, on a phone in your pocket, cannot be any of those things from a distance. Turning this on means somebody who knows your password still cannot get in.') }}
      </x-help>
    </x-slot:help>

    @if ($viewModel->usesTwoFactorAuthentication())
      @include('app.settings.account.security._two-factor-on')
    @else
      @include('app.settings.account.security._two-factor-off')
    @endif
  </x-box>
</x-app-layout>
