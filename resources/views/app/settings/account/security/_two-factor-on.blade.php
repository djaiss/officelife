{{-- What somebody sees once a code is asked for on top of their password. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
<div x-data="{ disabling: false, replacingCodes: false }" class="space-y-7">
  <div class="grid gap-7 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
    <div class="space-y-2.5">
      <p class="flex items-center gap-2 text-[15px] font-semibold text-ink">
        <span class="size-2 shrink-0 rounded-full bg-success" aria-hidden="true"></span>
        {{ __('On') }}
      </p>

      <p class="text-[15px] leading-relaxed text-body">{{ __('Turned on :time. We ask for a code every time you sign in.', ['time' => $viewModel->twoFactorConfirmedAt()]) }}</p>
    </div>

    <x-button.secondary type="button" @click="disabling = true" class="max-md:w-full">{{ __('Turn it off') }}</x-button.secondary>
  </div>

  <div class="space-y-4 border-t border-hairline-soft pt-6">
    <div class="grid gap-7 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
      <div class="space-y-2.5">
        <p class="text-[15px] font-semibold text-ink">{{ __('Recovery codes') }}</p>

        <p class="text-[15px] leading-relaxed text-body">{{ __('Keep these somewhere safe. Each one signs you in once, if you ever lose the phone.') }}</p>
      </div>

      <x-button.secondary type="button" @click="replacingCodes = true" class="max-md:w-full">{{ __('Get new codes') }}</x-button.secondary>
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

  <x-confirm-dialog
    show="disabling"
    close="disabling = false"
    labelledby="two-factor-off-title"
    :title="__('Turn two factor off?')"
    :cancel="__('Keep it on')"
  >
    {{ __('Your password alone will get you back in, and so will anybody else who has it. The recovery codes below stop working too.') }}

    <x-slot:actions>
      <x-form method="delete" :action="route('settings.twoFactor.destroy')">
        <x-button.danger>{{ __('Turn it off') }}</x-button.danger>
      </x-form>
    </x-slot:actions>
  </x-confirm-dialog>

  <x-confirm-dialog
    show="replacingCodes"
    close="replacingCodes = false"
    labelledby="recovery-codes-title"
    :title="__('Get new recovery codes?')"
    :cancel="__('Keep them')"
  >
    {{ __('The codes you have now stop working the moment new ones are made. Anywhere you wrote them down is out of date.') }}

    <x-slot:actions>
      <x-form method="post" :action="route('settings.recoveryCodes.create')">
        <x-button.danger>{{ __('Get new codes') }}</x-button.danger>
      </x-form>
    </x-slot:actions>
  </x-confirm-dialog>
</div>
