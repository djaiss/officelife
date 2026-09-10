{{-- The dialog that asks before an office is archived. --}}
{{--
  It is on the screen rather than inside the panel the question is asked from,
  because the panel clips what it holds and slides in on a transform, neither of
  which a dialog fixed to the window survives. It draws above the panel, which
  goes on naming the office behind it.

  The form swaps the counts, the list and the block of offices back in, the same
  as the forms in the panel, and closes the panel once the answer has landed.
--}}
<x-confirm-dialog
  show="archiving"
  close="archiving = false"
  labelledby="archive-location-title"
  :title="__('Archive this office?')"
  :cancel="__('Keep it open')"
>
  {{ __('The office leaves the list and nobody can be sent to it. Everything written about it is kept, and reopening it brings all of it back.') }}

  <x-slot:actions>
    <x-form
      method="post"
      x-bind:action="office?.archiveUrl"
      x-target="locations-table locations-stats locations-data"
      x-on:ajax:after="refresh(); close()"
    >
      <x-button.danger>{{ __('Archive it') }}</x-button.danger>
    </x-form>
  </x-slot:actions>
</x-confirm-dialog>
