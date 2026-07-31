{{--
  Shown after asking for a sign-in link, whether or not the address had an
  account behind it.

  @var \App\ViewModels\Auth\MagicLinkViewModel $viewModel
--}}
<x-guest-layout :title="__('Check your inbox')">
  <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
    <main class="flex min-w-0 flex-col justify-center px-6 py-10 sm:px-[60px]">
      <div class="mx-auto w-full max-w-[470px] space-y-[22px]">
        <div class="space-y-[9px]">
          <div class="flex items-center gap-[11px]">
            <x-logo :size="30" />

            <h1 class="text-[26px] font-semibold tracking-[-0.025em] text-ink">{{ __('Check your inbox') }}</h1>

            <x-theme-toggle class="ml-auto" />
          </div>

          <p class="text-sm text-body">
            {{ __('If that address has an account, a link is on its way. It works once, and only for the next :count minutes.', ['count' => $viewModel->minutes()]) }}
          </p>
        </div>

        <x-notice>
          {{ __('Nothing in your inbox? It can take a minute, and it sometimes lands in the spam folder.') }}
        </x-notice>

        <x-box padding="p-[15px]" class="rounded-lg text-center text-[13.5px] text-body">
          {{ __('Rather use your password?') }}
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
