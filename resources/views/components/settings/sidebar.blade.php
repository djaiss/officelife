{{--
  The column beside the settings screens: which company you are in, what you can
  change about your own account, and who you are signed in as.

  Preferences has no screen yet, so it is listed but not a link. A nav item that
  goes nowhere is worse than one that plainly cannot be clicked.

  @var string $companyName
  @var string $name
  @var \App\Models\Employee|null $employee
  @var string $current
--}}
@props([
  'companyName',
  'name',
  'employee' => null,
  'current' => 'profile',
])

@php
  $item = 'flex items-center gap-[10px] rounded-[7px] px-[10px] py-2 text-[13.5px]';
@endphp

<aside class="sticky top-0 flex h-screen flex-col border-r border-hairline bg-sunken">
  <div class="flex items-center gap-[10px] px-4 pt-[14px] pb-[10px]">
    <x-avatar-initials :name="$companyName" :size="26" />

    <span class="truncate text-sm font-semibold tracking-[-0.01em] text-ink">{{ $companyName }}</span>

    <x-theme-toggle class="ml-auto" />
  </div>

  <nav class="min-h-0 flex-1 space-y-px overflow-y-auto px-2 pt-2 pb-3">
    <h2 class="px-[10px] pt-[6px] pb-[5px] text-[10.5px] tracking-[0.09em] text-muted-soft uppercase">{{ __('Your profile') }}</h2>

    <a
      href="{{ route('settings.profile.index') }}"
      @if ($current === 'profile') aria-current="page" @endif
      class="{{ $item }} {{ $current === 'profile' ? 'bg-hover font-semibold text-ink' : 'text-body hover:bg-hover' }}"
    >
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="shrink-0" aria-hidden="true">
        <circle cx="8" cy="6" r="2.6"></circle>
        <path d="M3 14c0-2.6 2.2-4 5-4s5 1.4 5 4"></path>
      </svg>
      {{ __('Profile') }}
    </a>

    <a
      href="{{ route('settings.logs.index') }}"
      @if ($current === 'logs') aria-current="page" @endif
      class="{{ $item }} {{ $current === 'logs' ? 'bg-hover font-semibold text-ink' : 'text-body hover:bg-hover' }}"
    >
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="shrink-0" aria-hidden="true">
        <path d="M4 2.4h5.2L12.4 5.6V13.6H4z"></path>
        <line x1="6.2" y1="8" x2="10.2" y2="8"></line>
        <line x1="6.2" y1="10.6" x2="10.2" y2="10.6"></line>
      </svg>
      {{ __('Logs') }}
    </a>

    <span class="{{ $item }} text-muted-soft" aria-disabled="true">
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="shrink-0" aria-hidden="true">
        <rect x="2.4" y="3.2" width="11.2" height="9.6" rx="1.5"></rect>
        <line x1="2.4" y1="6.4" x2="13.6" y2="6.4"></line>
      </svg>
      {{ __('Preferences') }}
    </span>
  </nav>

  <div id="sidebar-identity" class="flex items-center gap-[9px] border-t border-hairline-soft px-3 py-[10px]">
    <x-avatar :employee="$employee" :name="$name" :size="26" />

    <span class="truncate text-[12.5px] font-semibold text-ink">{{ $name }}</span>
  </div>
</aside>
