<x-mail::message>
# Login attempt on {{ config('app.name') }}

Someone, hopefully you, tried to sign in to your account and failed.

<x-mail::panel>
If that was not you, sign in and change your password, just in case.
</x-mail::panel>

Thanks,<br>
{{ config('app.name') }}

</x-mail::message>
