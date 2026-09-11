{{-- The dialog that asks before an office is archived. --}}
<x-confirm-dialog
  show="archiving"
  close="archiving = false"
  labelledby="archive-office-title"
  :title="__('Archive this office?')"
  :cancel="__('Keep it open')"
>
  {{ __('The office leaves the list and nobody can be sent to it. Everything written about it is kept, and reopening it brings all of it back.') }}

  <x-slot:actions>
    <x-form
      method="post"
      x-bind:action="office?.archiveUrl"
      x-target="offices-table offices-statistics offices-data"
      x-on:ajax:after="refresh(); close()"
    >
      <x-button.danger>{{ __('Archive it') }}</x-button.danger>
    </x-form>
  </x-slot:actions>
</x-confirm-dialog>
