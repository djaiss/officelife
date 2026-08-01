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

    {{--
      One line of the log: who acted, what they did and how long ago.

      The raw name of the action is shown beside the sentence, in a monospaced
      face, because it is what somebody quotes when they ask us what happened.

      The row wraps rather than squeezing: when there is not enough width left
      for the sentence, the time drops onto a line of its own instead of shaving
      the sentence down to nothing.
    --}}
    @forelse ($viewModel->logs() as $log)
      <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-hairline-soft px-4 py-3 last:border-b-0">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="shrink-0 text-placeholder" aria-hidden="true">
          <path d="M1.5 8h3L6 5l2 6 2-4 1.4 1h3.1"></path>
        </svg>

        <div class="min-w-50 flex-1">
          <p class="flex flex-wrap items-baseline gap-x-2 text-sm">
            <span class="font-semibold text-ink">{{ $log->author }}</span>

            <span class="text-hairline-strong" aria-hidden="true">|</span>

            <span class="font-mono text-xs text-muted">{{ $log->action }}</span>
          </p>

          <p class="mt-0.5 text-sm text-body">{{ $log->description }}</p>
        </div>

        <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->toDayDateTimeString() }}" class="ml-auto shrink-0 font-mono text-xs text-muted-soft">
          {{ $log->created_at->diffForHumans() }}
        </time>
      </div>
    @empty
      <x-empty-state :title="__('No activity yet')">
        <x-slot:icon>
          <svg width="22" height="22" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1.5 8h3L6 5l2 6 2-4 1.4 1h3.1"></path>
          </svg>
        </x-slot:icon>

        {{ __('Nothing has happened on your account yet. When you or an administrator change your details, it shows up here.') }}
      </x-empty-state>
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
      @include('app.settings.account.logs._email-sent-row', ['emailSent' => $emailSent])
    @empty
      <x-empty-state :title="__('No emails yet')">
        <x-slot:icon>
          <svg width="22" height="22" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1.5" y="3.5" width="13" height="9" rx="1.5"></rect>
            <path d="m2 4.5 6 4.5 6-4.5"></path>
          </svg>
        </x-slot:icon>

        {{ __('Nothing has left our hands yet. The emails we send you, such as sign-in links and password changes, show up here once they do.') }}
      </x-empty-state>
    @endforelse

    @if ($viewModel->hasMoreEmailsSent())
      <div class="p-3 text-center text-sm">
        <x-link :href="route('settings.emailsSent.index')" turbo>{{ __('Browse all emails') }}</x-link>
      </div>
    @endif
  </x-box>
</x-app-layout>
