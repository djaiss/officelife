{{-- Everything that has been written down about an account: what its owner has done, and what we have sent them. --}}
{{--
  @var \App\ViewModels\Settings\Account\Logs\LogsViewModel $viewModel
--}}
<x-top-bar-layout :title="__('Logs')">
  <x-slot:top-bar>
    <x-top-bar :company-name="$viewModel->companyName()" :name="$viewModel->name()" :employee="$viewModel->employee()" />
  </x-slot:top-bar>

  <nav class="mt-5.5 mb-6.5 flex items-center gap-2.25 text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
    <a href="{{ route('home.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Dashboard') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <a href="{{ route('settings.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Settings') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <span class="font-medium text-ink" aria-current="page">{{ __('Logs') }}</span>
  </nav>

  <div class="mb-11">
    <h1 class="mb-2 text-4xl leading-tight font-bold tracking-tight text-ink">{{ __('Logs') }}</h1>
    <p class="text-lg leading-normal text-pretty text-body">{{ __('What we recorded about your account, and what we sent you.') }}</p>
  </div>

  <div class="space-y-10">
    <x-section :title="__('Activity')" icon="logs" :hue="200">
      <x-slot:help>
        <x-help :title="__('Activity')">
          {{ __('Every action you take that touches your account or your company is written down here, so you can tell what happened and when.') }}
        </x-help>
      </x-slot:help>

      <div class="space-y-6">
        <p class="text-[15px] leading-relaxed text-body">{{ __('Sensitive actions performed with your account are recorded here.') }}</p>

        {{--
          The next page is appended in place, and the link that asked for it is
          replaced by the one that came back, or removed on the last page.
        --}}
        <div id="logs-container" x-merge="append" class="overflow-hidden rounded-xl ring-[1.5px] ring-hairline">
          @forelse ($viewModel->logs() as $log)
            <x-box.row class="flex items-start gap-x-3 px-4 py-3.5">
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="mt-1 shrink-0 text-placeholder" aria-hidden="true">
                <path d="M1.5 8h3L6 5l2 6 2-4 1.4 1h3.1"></path>
              </svg>

              <div class="min-w-0 flex-1">
                <p class="flex flex-wrap items-baseline gap-x-2 text-sm">
                  <span class="font-semibold text-ink">{{ $log->author }}</span>

                  <span class="text-hairline-strong max-sm:hidden" aria-hidden="true">|</span>

                  <span class="font-mono text-xs wrap-anywhere text-muted max-sm:w-full">{{ $log->action }}</span>
                </p>

                <p class="mt-0.5 text-sm text-body">{{ $log->description }}</p>

                <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->toDayDateTimeString() }}" class="mt-1 block font-mono text-xs text-muted-soft sm:hidden">
                  {{ $log->created_at->diffForHumans() }}
                </time>
              </div>

              <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->toDayDateTimeString() }}" class="mt-0.5 shrink-0 font-mono text-xs whitespace-nowrap text-muted-soft max-sm:hidden">
                {{ $log->created_at->diffForHumans() }}
              </time>
            </x-box.row>
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
            <div id="pagination" class="border-t border-hairline-soft px-4 py-3 text-center text-sm">
              <x-link x-target="logs-container pagination" :href="$viewModel->logs()->nextPageUrl()">{{ __('Load more') }}</x-link>
            </div>
          @endif
        </div>
      </div>
    </x-section>

    <x-section :title="__('Emails sent')" icon="emails" :hue="50">
      <x-slot:help>
        <x-help :title="__('Emails sent')">
          {{ __('Every email we sent you, most recent first. If one you expected never arrived, look here first: an entry that is still on its way means the mail service has not confirmed it yet, and no entry at all means the email was never sent.') }}
        </x-help>
      </x-slot:help>

      <div class="space-y-6">
        <p class="text-[15px] leading-relaxed text-body">{{ __('The emails we sent to your account.') }}</p>

        <div class="overflow-hidden rounded-xl ring-[1.5px] ring-hairline">
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
            <div class="border-t border-hairline-soft px-4 py-3 text-center text-sm">
              <x-link :href="route('settings.emailsSent.index')" turbo>{{ __('Browse all emails') }}</x-link>
            </div>
          @endif
        </div>
      </div>
    </x-section>
  </div>
</x-top-bar-layout>
