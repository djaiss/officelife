{{--
  Where somebody who cannot remember their password asks for a way back in.

  @var \App\ViewModels\Auth\PasswordResetViewModel $viewModel
--}}
<x-guest-layout :title="__('Forgot your password?')">
  <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
    <main class="flex min-w-0 flex-col justify-center px-6 py-10 sm:px-[60px]">
      <div class="mx-auto w-full max-w-[470px] space-y-[22px]">
        <div class="space-y-[9px]">
          <div class="flex items-center gap-[11px]">
            <x-logo :size="30" />

            <h1 class="text-[26px] font-semibold tracking-[-0.025em] text-ink">{{ __('Forgot your password?') }}</h1>

            <x-theme-toggle class="ml-auto" />
          </div>

          <p class="text-sm text-body">{{ __('Give us your email address and we will send you a link to choose a new one.') }}</p>
        </div>

        <x-status :message="session('status')" />

        <x-box>
          <x-form method="post" :action="route('auth.password.create')" class="space-y-4">
            <x-input
              type="email"
              id="email"
              :label="__('Email address')"
              :value="old('email')"
              placeholder="john@doe.com"
              autocomplete="username"
              :error="$errors->get('email')"
              allowPasswordManager
              required
              autofocus
            />

            <x-button class="w-full">{{ __('Email me a link') }}</x-button>
          </x-form>
        </x-box>

        <x-box padding="p-[15px]" class="rounded-lg text-center text-[13.5px] text-body">
          {{ __('Remembered it after all?') }}
          <x-link :href="route('auth.login.new')" class="font-semibold text-ink">{{ __('Back to sign in') }}</x-link>
        </x-box>
      </div>

      <p class="mt-auto pt-10 text-center text-xs text-muted-soft">
        &copy; {{ config('app.name') }} {{ now()->format('Y') }} &middot; {{ __('Open source HR') }}
      </p>
    </main>

    @include('app.auth._quote', ['quote' => $viewModel->quote()])
  </div>
</x-guest-layout>
