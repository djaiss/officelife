<?php
/**
 * @var string $email
 * @var string $ip
 */
?>

<x-mail::message>
Hi,

Your {{ config('app.name') }} account {{ $email }} was just signed in to from a new location, device or browser.

<x-mail::panel>
Time: {{ now()->toDayDateTimeString() }}

IP address: {{ $ip }}
</x-mail::panel>

You are getting this email because we could not tell whether you had signed in from this location or browser before. That happens when you travel, use a VPN, update your browser, or when somebody else is using your account.

Thanks,<br>
{{ config('app.name') }}

</x-mail::message>
