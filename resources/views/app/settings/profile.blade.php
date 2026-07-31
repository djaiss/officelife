{{--
  Where somebody edits their own employee record: how colleagues see them, and
  who to call if something happens to them.

  @var \App\ViewModels\Settings\ProfileViewModel $viewModel
--}}
<x-app-layout :title="__('Profile')">
  <x-slot:sidebar>
    <x-settings.sidebar :company-name="$viewModel->companyName()" :name="$viewModel->name()" current="profile" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <nav class="text-[13.5px] text-muted" aria-label="{{ __('Breadcrumb') }}">
      {{ __('Settings') }}
      <span class="px-[5px] text-placeholder" aria-hidden="true">/</span>
      <span class="text-ink" aria-current="page">{{ __('Profile') }}</span>
    </nav>
  </x-slot:breadcrumb>

  <x-page-header
    :title="__('Profile')"
    :description="__('Manage your personal details and how colleagues see you.')"
  />

  {{-- Avatar --}}
  <x-box :title="__('Avatar')">
    <x-slot:help>
      <x-help :title="__('Avatar')">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
      </x-help>
    </x-slot:help>

    <div class="grid items-center gap-7 md:grid-cols-[minmax(0,1fr)_auto]">
      <div class="space-y-[10px] text-[13px] leading-relaxed text-body">
        <p>{{ __('Your avatar appears next to your name across :app.', ['app' => config('app.name')]) }}</p>
        <p>{{ __('Without an avatar, we show your initials instead.') }}</p>
      </div>

      <div class="flex items-center gap-4">
        <x-avatar-initials id="profile-avatar" :name="$viewModel->name()" :size="74" />

        <x-notice>{{ __('Changing your picture is coming soon.') }}</x-notice>
      </div>
    </div>
  </x-box>

  {{-- Details --}}
  <x-box :title="__('Details')">
    <x-slot:help>
      <x-help :title="__('Details')">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip.
      </x-help>
    </x-slot:help>

    <div class="grid gap-9 md:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
      <div class="space-y-[10px] text-[13px] leading-relaxed text-body">
        <p>{{ __('These details are shown on your profile. Everyone in your company can see them.') }}</p>
        <p>{{ __('The details you keep private, such as your emergency contact, are never shown to your colleagues.') }}</p>
      </div>

      <x-form
        method="put"
        :action="route('settings.profile.update')"
        id="details-form"
        x-target="details-form profile-avatar sidebar-identity"
        class="space-y-[14px] transition-opacity [&[aria-busy]]:opacity-60"
      >
        <div class="grid grid-cols-2 gap-3">
          <x-input
            id="first_name"
            :label="__('First name')"
            :value="$viewModel->details()['first_name']"
            :error="$errors->get('first_name')"
            required
          />

          <x-input
            id="last_name"
            :label="__('Last name')"
            :value="$viewModel->details()['last_name']"
            :error="$errors->get('last_name')"
            required
          />
        </div>

        <x-input
          id="display_name"
          :label="__('Display name')"
          :value="$viewModel->details()['display_name']"
          :placeholder="__('Shown instead of your real name')"
          :help="__('Optional.')"
          :error="$errors->get('display_name')"
        />

        <x-input
          type="email"
          id="work_email"
          :label="__('Work email')"
          :value="$viewModel->details()['work_email']"
          :error="$errors->get('work_email')"
        />

        <div class="flex items-center gap-3 pt-1">
          <span id="last-saved" @class(['text-xs text-muted-soft', 'hidden' => ! $viewModel->lastSavedAt()])>
            @if ($viewModel->lastSavedAt())
              {{ __('Last saved :time', ['time' => $viewModel->lastSavedAt()]) }}
            @endif
          </span>

          <x-button class="ml-auto">{{ __('Save') }}</x-button>
        </div>
      </x-form>
    </div>
  </x-box>

  {{-- Logs --}}
  <x-box :title="__('Logs')" padding="p-0">
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
      <p class="px-4 py-[13px] text-[13px] text-muted">{{ __('Nothing yet. Your actions show up here as you go.') }}</p>
    @endforelse

    @if ($viewModel->hasMoreLogs())
      <div class="border-t border-hairline-soft p-[13px] text-center text-[13px]">
        <x-link :href="route('settings.logs.index')" turbo>{{ __('Browse all activity') }}</x-link>
      </div>
    @endif
  </x-box>

  {{-- Emergency contact --}}
  <x-box :title="__('Emergency contact')">
    <x-slot:help>
      <x-help :title="__('Emergency contact')">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore.
      </x-help>
    </x-slot:help>

    <div class="grid gap-9 md:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
      <div class="space-y-[10px] text-[13px] leading-relaxed text-body">
        <p>{{ __('Who we should call if something happens to you at work.') }}</p>
        <p>{{ __('Only you and your company administrators can see this.') }}</p>
      </div>

      <x-form
        method="put"
        :action="route('settings.emergencyContact.update')"
        id="emergency-contact-form"
        x-target="emergency-contact-form last-saved"
        class="space-y-[14px] transition-opacity [&[aria-busy]]:opacity-60"
      >
        <div class="grid grid-cols-2 gap-3">
          <x-input
            id="name"
            :label="__('Name')"
            :value="$viewModel->emergencyContact()['name']"
            :error="$errors->get('name')"
          />

          <x-input
            id="phone"
            :label="__('Phone number')"
            :value="$viewModel->emergencyContact()['phone']"
            :error="$errors->get('phone')"
          />
        </div>

        <x-input
          id="relationship"
          :label="__('Relationship')"
          :value="$viewModel->emergencyContact()['relationship']"
          :placeholder="__('Partner, parent, friend')"
          :error="$errors->get('relationship')"
        />

        <div class="flex items-center pt-1">
          <x-button class="ml-auto">{{ __('Save') }}</x-button>
        </div>
      </x-form>
    </div>
  </x-box>
</x-app-layout>
