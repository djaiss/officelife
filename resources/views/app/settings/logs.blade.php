{{--
  Everything somebody has done with their account, newest first. The profile
  shows the last few; this is where the rest of them live.

  The next page is loaded in place: the link asks for it, the rows are appended
  to the ones already on screen, and the link itself is replaced by the one that
  came back, or removed when there is nothing left to read.

  @var \App\ViewModels\Settings\LogsViewModel $viewModel
--}}
<x-app-layout :title="__('Logs')">
  <x-slot:sidebar>
    <x-settings.sidebar :company-name="$viewModel->companyName()" :name="$viewModel->name()" current="profile" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <nav class="text-[13.5px] text-muted" aria-label="{{ __('Breadcrumb') }}">
      {{ __('Settings') }}
      <span class="px-[5px] text-placeholder" aria-hidden="true">/</span>
      <a href="{{ route('settings.profile.index') }}" class="hover:text-ink">{{ __('Profile') }}</a>
      <span class="px-[5px] text-placeholder" aria-hidden="true">/</span>
      <span class="text-ink" aria-current="page">{{ __('Logs') }}</span>
    </nav>
  </x-slot:breadcrumb>

  <x-page-header
    :title="__('Logs')"
    :description="__('Sensitive actions performed with your account are recorded here.')"
  />

  <x-box id="logs-container" x-merge="append" padding="p-0">
    @forelse ($viewModel->logs() as $log)
      <x-log-entry :log="$log" />
    @empty
      <p class="px-4 py-[13px] text-[13px] text-muted">{{ __('Nothing yet. Your actions show up here as you go.') }}</p>
    @endforelse

    @if ($viewModel->logs()->hasMorePages())
      <div id="pagination" class="border-t border-hairline-soft p-[13px] text-center text-[13px]">
        <x-link x-target="logs-container pagination" :href="$viewModel->logs()->nextPageUrl()">{{ __('Load more') }}</x-link>
      </div>
    @endif
  </x-box>
</x-app-layout>
