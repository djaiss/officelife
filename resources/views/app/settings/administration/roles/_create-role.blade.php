{{-- The dialog that makes a new role. --}}
{{--
  It asks for a name and nothing else. A role that grants nothing is harmless, so
  there is no reason to make somebody tick boxes before it exists. Starting from
  what another role grants is what the duplicate button on a role is for.

  Its own errors go in a bag of their own, since the screen behind it has a field
  called `name` too and the two messages would otherwise be the same message. The
  bag having anything in it is also what reopens the dialog after a save was
  turned away.

  Clicking away closes it, which is read off the backdrop itself rather than as a
  click outside the panel: the click that opens the dialog is still on its way up
  the document when the dialog appears, and an outside handler would catch that
  one and close it again straight away.

  @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel
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
    aria-labelledby="create-role-title"
    class="h-fit w-full max-w-115 rounded-[20px] bg-canvas px-6 pt-6 pb-7 shadow-2xl ring-[1.5px] ring-hairline sm:px-7"
  >
    <h2 id="create-role-title" class="text-2xl font-bold tracking-tight text-ink">{{ __('New role') }}</h2>

    <p class="mt-1.5 text-[15px] leading-relaxed text-pretty text-body">
      {{ __('A name is enough to start. It grants nothing until you tick something.') }}
    </p>

    <x-form method="post" :action="$viewModel->createUrl()" class="mt-5.5 space-y-4.5">
      <x-input
        id="name"
        :label="__('Name')"
        :value="old('name')"
        :placeholder="__('Regional people lead')"
        :error="$errors->createRole->get('name')"
        maxlength="255"
        required
      />

      <div class="flex flex-wrap items-center gap-2.5 pt-1">
        <x-button>{{ __('Create the role') }}</x-button>

        <x-button.secondary type="button" x-on:click="creating = false">{{ __('Cancel') }}</x-button.secondary>
      </div>
    </x-form>
  </div>
</div>
