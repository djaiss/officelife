{{-- Where somebody edits their own employee record: how colleagues see them, and who to call if something happens. --}}
{{--
  @var \App\ViewModels\Settings\Account\Profile\ProfileViewModel $viewModel
--}}
<x-top-bar-layout :title="__('Profile')">
  <x-slot:top-bar>
    <x-top-bar :company-name="$viewModel->companyName()" :name="$viewModel->name()" :employee="$viewModel->employee()" />
  </x-slot:top-bar>

  <nav class="mt-5.5 mb-6.5 flex items-center gap-2.25 text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
    <a href="{{ route('home.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Dashboard') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <a href="{{ route('settings.index') }}" data-turbo="true" class="transition-colors hover:text-ink">{{ __('Settings') }}</a>
    <span class="text-muted-soft" aria-hidden="true">/</span>
    <span class="font-medium text-ink" aria-current="page">{{ __('Profile') }}</span>
  </nav>

  <div class="mb-11">
    <h1 class="mb-2 text-4xl leading-tight font-bold tracking-tight text-ink">{{ __('Profile') }}</h1>
    <p class="text-lg leading-normal text-pretty text-body">{{ __('Manage your personal details and how colleagues see you.') }}</p>
  </div>

  <div class="space-y-10">
    <x-section :title="__('Avatar')" icon="profile" :hue="30">
      <x-slot:help>
        <x-help :title="__('Avatar')">
          {{ __('The picture is stored on our own servers, never on somebody else\'s, and is only ever served to people allowed to see you. Removing it puts your initials back everywhere.') }}
        </x-help>
      </x-slot:help>

      <div class="grid items-center gap-7 md:grid-cols-[minmax(0,1fr)_auto]">
        <div class="space-y-2 text-[15px] leading-relaxed text-body">
          <p>{{ __('Your avatar appears next to your name across :app.', ['app' => config('app.name')]) }}</p>
          <p>{{ __('Use a square image for the best result. JPEG, PNG and WebP up to 5 MB.') }}</p>
          <p>{{ __('Without an avatar, we show your initials instead.') }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-5">
          <x-avatar id="profile-avatar" :employee="$viewModel->employee()" :name="$viewModel->name()" :size="78" />

          {{--
            These two forms reload the page rather than updating it in place, the
            way the forms below do. A picture is not a field: it is read, turned
            upright, cropped and written twice before there is anything to show,
            and the screen is better off asking the server for the whole thing
            once that is done.

            The message lives in the x-data of this div rather than in the change
            handler, because Blade does not compile a directive written inside an
            attribute of a component tag: @js() would reach the browser as itself
            and Alpine would refuse the whole expression.
          --}}
          <div
            class="space-y-3 max-sm:w-full"
            x-data="{ tooBig: false, tooBigMessage: @js(__('The image must be under 5 MB.')) }"
          >
            <x-form
              method="post"
              :action="route('settings.photo.update')"
              :upload="true"
              id="photo-form"
              class="space-y-3"
              x-on:submit="if (tooBig) $event.preventDefault()"
            >
              <x-file-input
                id="photo"
                accept="image/jpeg,image/png,image/webp"
                :error="$errors->get('photo')"
                required
                x-on:change="tooBig = window.oversizedFiles($event.target.files, 5120).length > 0"
              />

              <p x-show="tooBig" x-cloak x-text="tooBigMessage" class="text-sm text-error"></p>

              <x-button>{{ __('Upload') }}</x-button>
            </x-form>

            @if ($viewModel->hasPhoto())
              {{-- Two steps rather than one, so a photo is never removed by a slip. --}}
              <x-form
                method="delete"
                :action="route('settings.photo.destroy')"
                id="photo-delete-form"
                x-data="{ confirming: false }"
              >
                <button
                  type="submit"
                  x-on:click="if (! confirming) { $event.preventDefault(); confirming = true }"
                  class="cursor-pointer text-sm text-muted hover:text-ink"
                  x-text="confirming ? @js(__('Remove it for good?')) : @js(__('Remove the photo'))"
                ></button>
              </x-form>
            @endif
          </div>
        </div>
      </div>
    </x-section>

    <x-section :title="__('Details')" icon="details" :hue="250">
      <x-slot:help>
        <x-help :title="__('Details')">
          {{ __('Your first and last name are how you are listed everywhere. A display name replaces them on screen without changing the record itself, which is what you want if you go by something other than your legal name.') }}
        </x-help>
      </x-slot:help>

      <div class="grid gap-10 md:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
        <div class="space-y-2.5 text-[15px] leading-relaxed text-body">
          <p>{{ __('These details are shown on your profile. Everyone in your company can see them.') }}</p>
          <p>{{ __('The details you keep private, such as your emergency contact, are never shown to your colleagues.') }}</p>
        </div>

        <x-form
          method="put"
          :action="route('settings.profile.update')"
          id="details-form"
          x-target="details-form profile-avatar top-bar-identity"
          class="space-y-5 transition-opacity [&[aria-busy]]:opacity-60"
        >
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

          <div class="flex items-center gap-3">
            <span id="last-saved" @class(['text-sm text-muted-soft', 'hidden' => ! $viewModel->lastSavedAt()])>
              @if ($viewModel->lastSavedAt())
                {{ __('Last saved :time', ['time' => $viewModel->lastSavedAt()]) }}
              @endif
            </span>

            <x-button class="ml-auto">{{ __('Save') }}</x-button>
          </div>
        </x-form>
      </div>
    </x-section>

    @if($viewModel->canSeePrivateInformation())
      <x-section :title="__('Emergency contact')" icon="emergency-contact" :hue="80">
        <x-slot:help>
          <x-help :title="__('Emergency contact')">
            {{ __('Leave it empty and there is nobody for us to call. It is worth a minute even if you never expect it to be read.') }}
          </x-help>
        </x-slot:help>

        <div class="grid gap-10 md:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
          <div class="space-y-2.5 text-[15px] leading-relaxed text-body">
            <p>{{ __('Who we should call if something happens to you at work.') }}</p>
            <p>{{ __('Only you and your company administrators can see this.') }}</p>
          </div>

          <x-form
            method="put"
            :action="route('settings.emergencyContact.update')"
            id="emergency-contact-form"
            x-target="emergency-contact-form last-saved"
            class="space-y-5 transition-opacity [&[aria-busy]]:opacity-60"
          >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <x-input
                id="name"
                :label="__('Name')"
                :value="$viewModel->emergencyContact()['name']"
                :placeholder="__('Full name')"
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

            <div class="flex items-center">
              <x-button class="ml-auto">{{ __('Save') }}</x-button>
            </div>
          </x-form>
        </div>
      </x-section>
    @endif
  </div>
</x-top-bar-layout>
