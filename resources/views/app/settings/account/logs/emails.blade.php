{{--
  Every email we sent to the person signed in, a page at a time.

  The list grows in place: the link at the bottom asks for the next page and
  alpine-ajax appends the rows that come back, then swaps the link for the one
  that came with them, or drops it on the last page.

  @var \App\ViewModels\Settings\Account\Logs\EmailsSentViewModel $viewModel
--}}
<x-app-layout :title="__('Emails sent')">
  <x-slot:sidebar>
    <x-settings.sidebar :company-name="$viewModel->companyName()" :name="$viewModel->name()" :employee="$viewModel->employee()" current="logs" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <nav class="text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
      {{ __('Settings') }}
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <x-link :href="route('settings.logs.index')" turbo>{{ __('Logs') }}</x-link>
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <span class="text-ink" aria-current="page">{{ __('Emails sent') }}</span>
    </nav>
  </x-slot:breadcrumb>

  <x-page-header
    :title="__('Emails sent')"
    :description="__('Every email we sent to your account, most recent first.')"
  />

  <x-box id="emails-sent-container" x-merge="append" padding="p-0">
    @forelse ($viewModel->emailsSent() as $emailSent)
      <x-email-sent-entry :email-sent="$emailSent" />
    @empty
      <p class="px-4 py-3 text-sm text-muted">{{ __('Nothing yet. The emails we send you show up here.') }}</p>
    @endforelse

    @if ($viewModel->emailsSent()->hasMorePages())
      <div id="pagination" class="p-3 text-center text-sm">
        <x-link x-target="emails-sent-container pagination" :href="$viewModel->emailsSent()->nextPageUrl()">{{ __('Load more') }}</x-link>
      </div>
    @endif
  </x-box>
</x-app-layout>
