{{--
  The panel that edits one office. It slides in over the table rather than
  replacing it, so the list is still there to move along.

  Everything it shows comes from the blob of offices declared on the layout tag,
  which is why there is one panel on the page rather than one per row. The form
  posts to whichever office is open, which is read off that blob too.

  The save buttons sit in the header of the panel rather than in the header of
  the screen, which the panel covers. They are not inside the form they save,
  since the archive button below is a form of its own and a form cannot hold
  another, so they reach it through the html `form` attribute instead.

  Archiving is a form of its own, which is why it sits in the footer rather than
  in the fields: a form cannot hold another. It asks first, since an office
  leaving the list is not what somebody meant to do by mistake.

  All three forms go over ajax and swap the counts, the table and the block of
  offices back in, so a save never takes the screen away from underneath the
  panel. `refresh()` runs on `ajax:after` rather than on `ajax:success`, because
  the latter is over before the answer has been merged and would read what was
  on the page a moment ago.

  @var \App\ViewModels\Settings\Administration\LocationsViewModel $viewModel
--}}
<x-side-modal show="open !== null" close="close()" labelledby="location-panel-title">
  <x-slot:header>
    <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-brand/12 text-xs font-semibold text-brand" x-text="office?.code"></span>

    <span class="min-w-0">
      <span id="location-panel-title" class="block truncate text-sm font-semibold tracking-tight text-ink" x-text="form.name"></span>

      <span class="mt-0.25 block truncate text-xs text-muted" x-text="office?.meta"></span>
    </span>

    <div x-cloak x-show="dirty" class="ml-auto flex shrink-0 items-center gap-2">
      <x-button.secondary type="button" x-on:click="discard()" class="py-1.5 text-xs [:where(&)]:px-2.75">
        {{ __('Discard') }}
      </x-button.secondary>

      <x-button form="location-form" class="py-1.5 text-xs [:where(&)]:px-3.25">{{ __('Save office') }}</x-button>
    </div>
  </x-slot:header>

  <x-form
    method="put"
    x-bind:action="office?.updateUrl"
    id="location-form"
    x-target="locations-table locations-stats locations-data location-errors"
    x-on:ajax:after="refresh()"
    class="space-y-4 transition-opacity [&[aria-busy]]:opacity-60"
  >
    {{-- What the panel was open on, so a save turned away by the validator opens it again on the same office. --}}
    <input type="hidden" name="location_id" x-bind:value="open" />

    {{-- The messages of a save the validator turned away. They are gathered in one
         place rather than under each field, because this is the block the answer
         swaps back in and the fields themselves are driven by alpine. --}}
    <div id="location-errors">
      @if ($errors->getBag('default')->any())
        <ul class="space-y-1 rounded-xl border border-error/25 bg-error/8 px-3.5 py-3 text-xs text-error">
          @foreach ($errors->getBag('default')->all() as $message)
            <li>{{ $message }}</li>
          @endforeach
        </ul>
      @endif
    </div>

    <div class="grid gap-3.5 rounded-xl border border-hairline bg-canvas p-4 sm:grid-cols-2">
      <x-input
        id="name"
        x-model="form.name"
        :label="__('Name')"
        minlength="2"
        maxlength="255"
        required
      />

      <x-input
        id="country"
        x-model="form.country"
        :label="__('Country')"
        :help="__('Two letters, such as US')"
        maxlength="2"
        pattern="[A-Za-z]{2}"
        class="uppercase"
      />

      <x-input
        id="city"
        x-model="form.city"
        :label="__('City')"
        maxlength="255"
      />

      <div class="space-y-1.5">
        <x-label for="timezone" :value="__('Time zone')" />

        <select
          id="timezone"
          name="timezone"
          x-model="form.timezone"
          class="block w-full appearance-none rounded-md border border-hairline-strong bg-input px-3 py-2.5 text-base text-ink transition-colors duration-150 hover:border-focus hover:bg-hover focus:border-focus focus:bg-canvas focus:outline-none sm:text-sm"
        >
          <option value="">{{ __('Same as the company') }}</option>

          @foreach ($viewModel->timezones() as $timezone)
            <option value="{{ $timezone }}">{{ $timezone }}</option>
          @endforeach
        </select>
      </div>

      <div class="sm:col-span-2">
        <x-input
          id="address"
          x-model="form.address"
          :label="__('Address')"
          :placeholder="__('Street and postal code')"
          maxlength="500"
        />
      </div>

      <div class="sm:col-span-2">
        <x-checkbox id="is_primary" x-model="form.isPrimary">
          {{ __('This is the head office') }}
        </x-checkbox>

        <p class="mt-1.5 text-xs text-muted">
          {{ __('A company keeps one head office. Ticking this takes the badge off whichever office had it, and the only way to remove it is to give it to another office.') }}
        </p>
      </div>
    </div>

    <p class="rounded-xl border border-hairline-soft bg-sunken px-3.5 py-3 text-xs leading-relaxed text-muted" x-text="office?.inheritNote"></p>
  </x-form>

  <x-slot:footer>
    <div class="flex flex-wrap items-center gap-2.5">
      <template x-if="office?.isArchived">
        <div class="flex w-full flex-wrap items-center gap-2.5">
          <p class="text-xs text-muted">{{ __('This office is archived and nobody can be sent to it.') }}</p>

          <x-form
            method="delete"
            x-bind:action="office?.restoreUrl"
            x-target="locations-table locations-stats locations-data"
            x-on:ajax:after="refresh(); close()"
            class="ml-auto"
          >
            <x-button.secondary class="py-1.75 text-xs [:where(&)]:px-3">{{ __('Reopen this office') }}</x-button.secondary>
          </x-form>
        </div>
      </template>

      <template x-if="office && ! office.isArchived">
        <div class="flex w-full flex-wrap items-center gap-2.5">
          <div x-show="! confirming" class="flex w-full flex-wrap items-center gap-2.5">
            <p class="text-xs text-muted">{{ __('Everything written about the office is kept.') }}</p>

            <x-button.secondary type="button" x-on:click="confirming = true" class="ml-auto py-1.75 text-xs [:where(&)]:px-3">
              {{ __('Archive this office') }}
            </x-button.secondary>
          </div>

          <div x-cloak x-show="confirming" class="w-full space-y-2.5">
            <p class="text-xs text-error">{{ __('The office leaves the list and nobody can be sent to it. Sure?') }}</p>

            <div class="flex items-center justify-end gap-2.5">
              <x-button.secondary type="button" x-on:click="confirming = false" class="py-1.75 text-xs [:where(&)]:px-3">
                {{ __('Keep it open') }}
              </x-button.secondary>

              <x-form
                method="post"
                x-bind:action="office?.archiveUrl"
                x-target="locations-table locations-stats locations-data"
                x-on:ajax:after="refresh(); close()"
              >
                <x-button class="bg-error py-1.75 text-xs hover:bg-error/88 [:where(&)]:px-3">{{ __('Archive it') }}</x-button>
              </x-form>
            </div>
          </div>
        </div>
      </template>
    </div>
  </x-slot:footer>
</x-side-modal>
