{{--
  The dialog that makes a new role. It can start from nothing, or from the
  permissions of a role that already exists, which is the difference between
  ticking seven boxes and ticking one.

  Its own errors go in a bag of their own, since the screen behind it has a field
  called `name` too and the two messages would otherwise be the same message. The
  bag having anything in it is also what reopens the dialog after a save was
  turned away.

  It carries `data-escape-guard` while it is open, so escape closes the dialog
  rather than leaving the layer underneath it.

  Clicking away closes it too, which is read off the backdrop itself rather than
  as a click outside the panel: the click that opens the dialog is still on its
  way up the document when the dialog appears, and an outside handler would
  catch that one and close it again straight away.

  The slug is worked out here only to be shown. What the role is finally known by
  is decided by the action that creates it, which also has to keep it free.

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
  class="fixed inset-0 z-60 flex justify-center overflow-y-auto bg-black/35 px-4 py-14"
>
  <div
    x-data="{ draft: @js(old('name', '')) }"
    role="dialog"
    aria-modal="true"
    aria-labelledby="create-role-title"
    class="h-fit w-full max-w-114 rounded-xl border border-hairline bg-canvas shadow-xl"
  >
    <x-form method="post" :action="route('settings.roles.create')">
      <div class="space-y-1 border-b border-hairline-soft px-4.5 py-3.5">
        <h2 id="create-role-title" class="text-sm font-semibold tracking-tight text-ink">{{ __('New role') }}</h2>

        <p class="text-xs text-muted">{{ __('Start with nothing, or copy what an existing role is allowed to do and change it from there.') }}</p>
      </div>

      <div class="space-y-4 px-4.5 py-4">
        <x-input
          id="name"
          x-model="draft"
          :label="__('Name')"
          :value="old('name')"
          :placeholder="__('Regional people lead')"
          :error="$errors->createRole->get('name')"
          maxlength="255"
          required
        />

        <fieldset class="space-y-1.5">
          <legend class="text-xs text-body">{{ __('Copy permissions from') }}</legend>

          <div class="flex flex-wrap gap-1.5">
            @foreach ($viewModel->templates() as $template)
              <label class="block">
                <input
                  type="radio"
                  name="copy_from"
                  value="{{ $template['id'] }}"
                  @checked((string) old('copy_from', '') === $template['id'])
                  class="peer sr-only"
                />

                <span class="block cursor-pointer rounded-full border border-hairline-strong bg-canvas px-2.75 py-1.25 text-xs text-body transition-colors hover:bg-hover peer-checked:border-primary peer-checked:bg-primary peer-checked:text-on-primary peer-focus-visible:ring-2 peer-focus-visible:ring-focus">
                  {{ $template['name'] }}
                </span>
              </label>
            @endforeach
          </div>
        </fieldset>
      </div>

      <div class="flex flex-wrap items-center gap-2 border-t border-hairline-soft px-4.5 py-3">
        <p class="text-xs text-muted-soft">
          {{ __('Slug') }}

          <span class="font-mono" x-text="draft.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || @js(__('role'))"></span>
        </p>

        <div class="ml-auto flex gap-2">
          <x-button.secondary type="button" x-on:click="creating = false">{{ __('Cancel') }}</x-button.secondary>

          <x-button>{{ __('Create role') }}</x-button>
        </div>
      </div>
    </x-form>
  </div>
</div>
