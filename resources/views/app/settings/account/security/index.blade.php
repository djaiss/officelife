{{--
  What somebody can change about the way they sign in: their password, and
  whether a code is asked for on top of it.

  The form saves in place: it asks for the screen again and swaps itself for the
  one that comes back, so a mistyped current password lands under its field
  without the page moving, and a save that works comes back with empty fields
  and the toast the redirect carries.

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

  {{-- Change password --}}
  <x-box :title="__('Change password')">
    <x-slot:help>
      <x-help :title="__('Change password')">
        {{ __('We never store your password itself, only a hash of it, which is why we ask for the current one rather than showing it to you. Changing it is written to your logs, so an account you share by accident leaves a trace.') }}
      </x-help>
    </x-slot:help>

    <div class="grid gap-9 md:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
      <div class="space-y-2.5 text-sm leading-relaxed text-body">
        <p>{{ __('Choose a password you use nowhere else. Long beats complicated.') }}</p>
        <p>{{ __('You stay signed in here, and your new password is what you type the next time.') }}</p>
      </div>

      @if ($viewModel->usesSingleSignOn())
        <p class="text-sm leading-relaxed text-muted">{{ __('You sign in through your identity provider, so there is no password to change here.') }}</p>
      @else
        <x-form
          method="put"
          :action="route('settings.password.update')"
          id="password-form"
          x-target="password-form"
          class="space-y-3.5 transition-opacity [&[aria-busy]]:opacity-60"
        >
          <x-input
            type="password"
            id="current_password"
            :label="__('Current password')"
            autocomplete="current-password"
            :error="$errors->get('current_password')"
            allowPasswordManager
            required
          />

          <div class="space-y-1.5">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <x-input
                type="password"
                id="new_password"
                :label="__('New password')"
                autocomplete="new-password"
                passwordrules="minlength: 8"
                :error="$errors->get('new_password')"
                allowPasswordManager
                required
              />

              <x-input
                type="password"
                id="new_password_confirmation"
                :label="__('Confirm new password')"
                autocomplete="new-password"
                passwordrules="minlength: 8"
                :error="$errors->get('new_password_confirmation')"
                allowPasswordManager
                required
              />
            </div>

            <p class="text-xs text-muted">{{ __('Minimum 8 characters.') }}</p>
          </div>

          <div class="flex items-center gap-3 pt-1">
            <span @class(['text-xs text-muted-soft', 'hidden' => ! $viewModel->passwordChangedAt()])>
              @if ($viewModel->passwordChangedAt())
                {{ __('Last changed :time', ['time' => $viewModel->passwordChangedAt()]) }}
              @endif
            </span>

            <x-button class="ml-auto">{{ __('Save') }}</x-button>
          </div>
        </x-form>
      @endif
    </div>
  </x-box>

  {{-- Two factor authentication --}}
  <x-box :title="__('Two factor authentication')">
    <x-slot:help>
      <x-help :title="__('Two factor authentication')">
        {{ __('A password can be guessed, reused or stolen. A code that changes every thirty seconds, on a phone in your pocket, cannot be any of those things from a distance. Turning this on means somebody who knows your password still cannot get in.') }}
      </x-help>
    </x-slot:help>

    @if (! $viewModel->usesTwoFactorAuthentication())
      <div class="grid gap-9 md:grid-cols-[minmax(0,1fr)_auto]">
        <div class="space-y-2.5 text-sm leading-relaxed text-body">
          <p>{{ __('Ask us for a code from your authenticator app every time you sign in, on top of your password.') }}</p>
          <p>{{ __('You will need an authenticator app on your phone. Any of them works.') }}</p>
        </div>

        <div class="flex items-center">
          <x-button :href="route('settings.twoFactor.new')">{{ __('Turn it on') }}</x-button>
        </div>
      </div>
    @else
      {{--
        The two destructive buttons ask before they act rather than after: each
        one swaps itself for a short sentence saying what is about to be lost,
        and the button that goes through with it.
      --}}
      <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
          <div class="min-w-0 space-y-1">
            <p class="flex items-center gap-2 text-sm font-semibold text-ink">
              <span class="size-2 shrink-0 rounded-full bg-success" aria-hidden="true"></span>
              {{ __('On') }}
            </p>

            <p class="text-sm text-muted">{{ __('Turned on :time. We ask for a code every time you sign in.', ['time' => $viewModel->twoFactorConfirmedAt()]) }}</p>
          </div>

          <div x-data="{ confirming: false }" class="ml-auto shrink-0">
            <x-button.secondary type="button" x-show="! confirming" @click="confirming = true">{{ __('Turn it off') }}</x-button.secondary>

            <div x-cloak x-show="confirming" class="flex flex-wrap items-center gap-3">
              <p class="text-sm text-error">{{ __('Your password alone will get you in again. Sure?') }}</p>

              <x-button.secondary type="button" @click="confirming = false">{{ __('Keep it on') }}</x-button.secondary>

              <x-form method="delete" :action="route('settings.twoFactor.destroy')">
                <x-button class="bg-error hover:bg-error/88">{{ __('Turn it off') }}</x-button>
              </x-form>
            </div>
          </div>
        </div>

        <div class="space-y-3 border-t border-hairline-soft pt-5">
          <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
            <div class="min-w-0 space-y-1">
              <p class="text-sm font-semibold text-ink">{{ __('Recovery codes') }}</p>

              <p class="text-sm text-muted">{{ __('Keep these somewhere safe. Each one signs you in once, if you ever lose the phone.') }}</p>
            </div>

            <div x-data="{ confirming: false }" class="ml-auto shrink-0">
              <x-button.secondary type="button" x-show="! confirming" @click="confirming = true">{{ __('Get new codes') }}</x-button.secondary>

              <div x-cloak x-show="confirming" class="flex flex-wrap items-center gap-3">
                <p class="text-sm text-error">{{ __('The codes below stop working. Sure?') }}</p>

                <x-button.secondary type="button" @click="confirming = false">{{ __('Keep them') }}</x-button.secondary>

                <x-form method="post" :action="route('settings.recoveryCodes.create')">
                  <x-button class="bg-error hover:bg-error/88">{{ __('Get new codes') }}</x-button>
                </x-form>
              </div>
            </div>
          </div>

          @if ($viewModel->recoveryCodes() === [])
            <p class="text-sm text-muted-soft">{{ __('You have used every code. Ask for new ones before you need them.') }}</p>
          @else
            <ul class="grid gap-1.5 rounded-lg border border-hairline bg-sunken px-4 py-3.5 font-mono text-sm text-ink select-all sm:grid-cols-2">
              @foreach ($viewModel->recoveryCodes() as $code)
                <li>{{ $code }}</li>
              @endforeach
            </ul>
          @endif
        </div>
      </div>
    @endif
  </x-box>
</x-app-layout>
