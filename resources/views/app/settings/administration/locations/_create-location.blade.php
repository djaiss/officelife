{{--
  The dialog that adds an office. It asks for the little that tells one office
  from another, and leaves the address and the time zone to the panel, so a
  company opening a branch is not made to look up a time zone first.

  Its own errors go in a bag of their own, since the panel behind it has a field
  called `name` too and the two messages would otherwise be the same message. The
  bag having anything in it is also what reopens the dialog after a save was
  turned away.

  It carries `data-escape-guard` while it is open, so escape closes the dialog
  rather than leaving the layer underneath it.

  Clicking away closes it too, which is read off the backdrop itself rather than
  as a click outside the panel: the click that opens the dialog is still on its
  way up the document when the dialog appears, and an outside handler would catch
  that one and close it again straight away.

  @var \App\ViewModels\Settings\Administration\LocationsViewModel $viewModel
--}}
<div
  x-cloak
  x-show="creating"
  x-transition.opacity.duration.110ms
  x-on:keydown.escape="creating = false"
  x-on:click.self="creating = false"
  x-effect="if (creating) { $nextTick(() => $el.querySelector('input[name=name]').focus()) }"
  :data-escape-guard="creating ? '' : null"
  class="fixed inset-0 z-70 flex justify-center overflow-y-auto bg-black/35 px-4 py-14"
>
  <div
    role="dialog"
    aria-modal="true"
    aria-labelledby="create-location-title"
    class="h-fit w-full max-w-114 rounded-xl border border-hairline bg-canvas shadow-xl"
  >
    <x-form method="post" :action="$viewModel->createUrl()">
      <div class="space-y-1 border-b border-hairline-soft px-4.5 py-3.5">
        <h2 id="create-location-title" class="text-sm font-semibold tracking-tight text-ink">{{ __('New location') }}</h2>

        <p class="text-xs text-muted">{{ __('People can be sent to it as soon as it exists.') }}</p>
      </div>

      <div class="space-y-4 px-4.5 py-4">
        <x-input
          id="name"
          :label="__('Name')"
          :value="old('name')"
          :placeholder="__('Berlin office')"
          :error="$errors->createLocation->get('name')"
          maxlength="255"
          required
        />

        <div class="grid gap-4 sm:grid-cols-2">
          <x-input
            id="city"
            :label="__('City')"
            :value="old('city')"
            :placeholder="__('Berlin')"
            :error="$errors->createLocation->get('city')"
            maxlength="255"
          />

          <x-input
            id="country"
            :label="__('Country')"
            :value="old('country')"
            :placeholder="__('DE')"
            :help="__('Two letters')"
            :error="$errors->createLocation->get('country')"
            maxlength="2"
            class="uppercase"
          />
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2 border-t border-hairline-soft px-4.5 py-3">
        <p class="text-xs text-muted-soft">{{ __('The address and the time zone come after.') }}</p>

        <div class="ml-auto flex gap-2">
          <x-button.secondary type="button" x-on:click="creating = false">{{ __('Cancel') }}</x-button.secondary>

          <x-button>{{ __('Create location') }}</x-button>
        </div>
      </div>
    </x-form>
  </div>
</div>
