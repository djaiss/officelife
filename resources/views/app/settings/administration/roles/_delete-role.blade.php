{{-- The dialog that asks before a role is deleted. --}}
{{-- @var array $role --}}
<x-confirm-dialog
  show="deleting"
  close="deleting = false"
  labelledby="delete-role-title"
  :title="__('Delete :role?', ['role' => $role['name']])"
  :cancel="__('Keep it')"
>
  {{ __('Whatever this role granted, it grants no longer, and everybody who holds it loses it. This cannot be undone.') }}

  <x-slot:actions>
    <x-form method="delete" :action="$role['destroyUrl']">
      <x-button.danger>{{ __('Delete this role') }}</x-button.danger>
    </x-form>
  </x-slot:actions>
</x-confirm-dialog>
