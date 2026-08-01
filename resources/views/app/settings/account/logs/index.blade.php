{{--
  Everything that has been written down about an account: what its owner has
  done, and what we have sent them.

  The next page of logs is loaded in place: the link asks for it, the rows are
  appended to the ones already on screen, and the link itself is replaced by the
  one that came back, or removed when there is nothing left to read.

  @var \App\ViewModels\Settings\Account\Logs\LogsViewModel $viewModel
--}}
<x-app-layout :title="__('Logs')">
  <x-slot:sidebar>
    <x-settings.sidebar :company-name="$viewModel->companyName()" :name="$viewModel->name()" :employee="$viewModel->employee()" current="logs" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <nav class="text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
      {{ __('Settings') }}
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <span class="text-ink" aria-current="page">{{ __('Logs') }}</span>
    </nav>
  </x-slot:breadcrumb>

  <x-page-header
    :title="__('Logs')"
    :description="__('What we recorded about your account, and what we sent you.')"
  />

  {{-- Logs --}}
  <x-box :title="__('Logs')" id="logs-container" x-merge="append" padding="p-0">
    <x-slot:help>
      <x-help :title="__('Logs')">
        {{ __('Every action you take that touches your account or your company is written down here, so you can tell what happened and when.') }}
      </x-help>
    </x-slot:help>

    <x-slot:description>
      <p>{{ __('Sensitive actions performed with your account are recorded here.') }}</p>
    </x-slot:description>

    @forelse ($viewModel->logs() as $log)
      <x-log-entry :log="$log" />
    @empty
      <p class="px-4 py-3 text-sm text-muted">{{ __('Nothing yet. Your actions show up here as you go.') }}</p>
    @endforelse

    @if ($viewModel->logs()->hasMorePages())
      <div id="pagination" class="p-3 text-center text-sm">
        <x-link x-target="logs-container pagination" :href="$viewModel->logs()->nextPageUrl()">{{ __('Load more') }}</x-link>
      </div>
    @endif
  </x-box>

  {{-- Emails sent --}}
  <x-box :title="__('Emails sent')" padding="p-0">
    <x-slot:help>
      <x-help :title="__('Emails sent')">
        {{ __('Every email we sent you, most recent first. If one you expected never arrived, look here first: an entry that is still on its way means the mail service has not confirmed it yet, and no entry at all means the email was never sent.') }}
      </x-help>
    </x-slot:help>

    <x-slot:description>
      <p>{{ __('The emails we sent to your account.') }}</p>
    </x-slot:description>

    @forelse ($viewModel->emailsSent() as $emailSent)
      <x-email-sent-entry :email-sent="$emailSent" />
    @empty
      <p class="px-4 py-3 text-sm text-muted">{{ __('Nothing yet. The emails we send you show up here.') }}</p>
    @endforelse

    @if ($viewModel->hasMoreEmailsSent())
      <div class="p-3 text-center text-sm">
        <x-link :href="route('settings.emailsSent.index')" turbo>{{ __('Browse all emails') }}</x-link>
      </div>
    @endif
  </x-box>
</x-app-layout>
