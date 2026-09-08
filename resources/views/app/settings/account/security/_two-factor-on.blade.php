{{--
  What somebody sees once a code is asked for on top of their password: when it
  was turned on, the way back out, and the codes that get them in if they ever
  lose the phone.

  Each destructive button swaps itself for what is about to be lost and the
  button that goes through with it, so nothing here acts on the first click.

  @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel
--}}
<div class="space-y-7">
  <div class="grid gap-7 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
    <div class="space-y-2.5">
      <p class="flex items-center gap-2 text-[15px] font-semibold text-ink">
        <span class="size-2 shrink-0 rounded-full bg-success" aria-hidden="true"></span>
        {{ __('On') }}
      </p>

      <p class="text-[15px] leading-relaxed text-body">{{ __('Turned on :time. We ask for a code every time you sign in.', ['time' => $viewModel->twoFactorConfirmedAt()]) }}</p>
    </div>

    <div x-data="{ confirming: false }">
      <x-button.secondary type="button" x-show="! confirming" @click="confirming = true" class="max-md:w-full">{{ __('Turn it off') }}</x-button.secondary>

      <div x-cloak x-show="confirming" class="flex flex-wrap items-center gap-3 max-md:flex-col max-md:items-stretch">
        <p class="text-sm text-error max-md:text-center">{{ __('Your password alone will get you in again. Sure?') }}</p>

        <x-button.secondary type="button" @click="confirming = false">{{ __('Keep it on') }}</x-button.secondary>

        <x-form method="delete" :action="route('settings.twoFactor.destroy')">
          <x-button class="bg-error hover:bg-error/88 max-md:w-full">{{ __('Turn it off') }}</x-button>
        </x-form>
      </div>
    </div>
  </div>

  <div class="space-y-4 border-t border-hairline-soft pt-6">
    <div class="grid gap-7 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
      <div class="space-y-2.5">
        <p class="text-[15px] font-semibold text-ink">{{ __('Recovery codes') }}</p>

        <p class="text-[15px] leading-relaxed text-body">{{ __('Keep these somewhere safe. Each one signs you in once, if you ever lose the phone.') }}</p>
      </div>

      <div x-data="{ confirming: false }">
        <x-button.secondary type="button" x-show="! confirming" @click="confirming = true" class="max-md:w-full">{{ __('Get new codes') }}</x-button.secondary>

        <div x-cloak x-show="confirming" class="flex flex-wrap items-center gap-3 max-md:flex-col max-md:items-stretch">
          <p class="text-sm text-error max-md:text-center">{{ __('The codes below stop working. Sure?') }}</p>

          <x-button.secondary type="button" @click="confirming = false">{{ __('Keep them') }}</x-button.secondary>

          <x-form method="post" :action="route('settings.recoveryCodes.create')">
            <x-button class="bg-error hover:bg-error/88 max-md:w-full">{{ __('Get new codes') }}</x-button>
          </x-form>
        </div>
      </div>
    </div>

    @if ($viewModel->recoveryCodes() === [])
      <p class="text-[15px] text-muted-soft">{{ __('You have used every code. Ask for new ones before you need them.') }}</p>
    @else
      <ul class="grid gap-1.5 rounded-xl bg-sunken px-4 py-3.5 font-mono text-sm text-ink ring-[1.5px] ring-hairline select-all sm:grid-cols-2">
        @foreach ($viewModel->recoveryCodes() as $code)
          <li>{{ $code }}</li>
        @endforeach
      </ul>
    @endif
  </div>
</div>
