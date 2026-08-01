{{--
  The password half of the security screen.

  The form saves in place: it asks for the screen again and swaps itself for the
  one that comes back, so a mistyped current password lands under its field
  without the page moving, and a save that works comes back with empty fields
  and the toast the redirect carries.

  Somebody who signs in through an identity provider has no password here to
  change, so they are told as much instead.

  @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel
--}}
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
