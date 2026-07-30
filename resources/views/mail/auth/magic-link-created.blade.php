<?php
/**
 * @var string $link
 */
?>

<x-mail::message>
# Your login link for {{ config('app.name') }}

<x-mail::button :url="$link">
Login to {{ config('app.name') }}
</x-mail::button>

This link is only valid for the next 5 minutes.

<x-mail::panel>
If you did not ask for this link, sign in and change your password, just in case.
</x-mail::panel>

Thanks,<br>
{{ config('app.name') }}

</x-mail::message>
