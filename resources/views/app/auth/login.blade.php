{{--
  Where somebody who already has an account signs back in.

  @var \App\ViewModels\Auth\LoginViewModel $viewModel
--}}
<x-guest-layout :title="__('Welcome back')">
  <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
    {{-- The form --}}
    <main class="flex min-w-0 flex-col px-6 pt-10 pb-8 sm:px-[60px] sm:pt-14">
      <div class="mx-auto w-full max-w-[470px] space-y-[22px]">
        <div class="flex items-center gap-[11px]">
          <x-logo :size="30" />

          <h1 class="text-[26px] font-semibold tracking-[-0.025em] text-ink">{{ __('Welcome back') }}</h1>

          <x-theme-toggle class="ml-auto" />
        </div>

        <x-status :message="session('status')" />

        <x-box>
          <x-form method="post" :action="route('auth.login.create')" class="space-y-4">
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

            <x-input
              type="password"
              id="password"
              :label="__('Password')"
              autocomplete="current-password"
              :error="$errors->get('password')"
              allowPasswordManager
              required
            />

            <x-checkbox id="remember" class="w-fit" :checked="(bool) old('remember')">
              {{ __('Remember me for :count days', ['count' => config('officelife.remember_duration_days')]) }}
            </x-checkbox>

            <x-turnstile data-size="flexible" />

            <div class="flex items-center gap-[14px]">
              <x-link turbo :href="route('auth.password.new')" class="text-[13px] text-body">
                {{ __('Forgot your password?') }}
              </x-link>

              <x-button class="ml-auto">{{ __('Continue') }}</x-button>
            </div>
          </x-form>
        </x-box>

        {{-- The shortcut that signs the seeded account in, only ever on a machine somebody develops on --}}
        @env(config('login-link.allowed_environments'))
          <x-box padding="p-[15px]" class="rounded-lg text-center text-[13.5px] text-body">
            <x-login-link
              label="Sign in as Michael Scott"
              email="michael.scott@dundermifflin.com"
              :redirect-url="route('home.index')"
              class="inline cursor-pointer font-semibold text-ink underline underline-offset-2 decoration-hairline-strong transition-colors duration-150 hover:text-brand hover:decoration-brand"
            />
          </x-box>
        @endenv

        <x-box padding="p-[15px]" class="rounded-lg text-center text-[13.5px] text-body">
          {{ __('Wanna skip the password?') }}
          <x-link turbo :href="route('auth.magicLink.new')" class="font-semibold text-ink">{{ __('Send me a link instead') }}</x-link>
        </x-box>

        <x-box padding="p-[15px]" class="rounded-lg text-center text-[13.5px] text-body">
          {{ __('New to :app?', ['app' => config('app.name')]) }}
          <x-link turbo :href="route('auth.register.new')" class="font-semibold text-ink">{{ __('Create a company') }}</x-link>
        </x-box>

        <div class="flex items-center gap-3">
          <x-language-picker :locales="$viewModel->locales()" :current="$viewModel->currentLocale()" />

          <span class="text-[12.5px] text-muted-soft">{{ __('You can change it any time.') }}</span>
        </div>
      </div>

      <p class="mt-auto pt-10 text-center text-xs text-muted-soft">
        &copy; {{ config('app.name') }} {{ now()->format('Y') }} &middot; {{ __('Open source HR') }}
      </p>
    </main>

    {{-- The quote --}}
    @include('app.auth._quote', ['quote' => $viewModel->quote()])
  </div>
</x-guest-layout>
