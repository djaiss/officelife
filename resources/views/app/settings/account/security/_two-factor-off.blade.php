{{-- What somebody sees when a code is not asked for on top of their password. --}}
<div class="grid gap-7 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
  <div class="space-y-2.5">
    <p class="text-[15px] font-semibold text-ink">{{ __('Authenticator app') }}</p>

    <div class="space-y-2.5 text-[15px] leading-relaxed text-body">
      <p>{{ __('Ask us for a code from your authenticator app every time you sign in, on top of your password.') }}</p>
      <p>{{ __('You will need an authenticator app on your phone. Any of them works.') }}</p>
    </div>
  </div>

  <x-button :href="route('settings.twoFactor.new')" data-turbo="true" class="max-md:w-full">{{ __('Turn it on') }}</x-button>
</div>
