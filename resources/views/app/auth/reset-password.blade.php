<?php
/**
 * @var \Illuminate\Http\Request $request
 */
?>
{{--
    Placeholder screen. It carries the fields the controller expects and nothing
    else: no layout, no styling. Replace it with the real design.
--}}
<h1>Choose a new password</h1>

@if ($errors->any())
  <ul>
    @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </ul>
@endif

<form method="POST" action="{{ route('password.store') }}">
  @csrf
  <input type="hidden" name="token" value="{{ $request->route('token') }}">
  <input type="email" name="email" value="{{ old('email', $request->input('email')) }}" placeholder="Email">
  <input type="password" name="password" placeholder="New password">
  <input type="password" name="password_confirmation" placeholder="Confirm the new password">
  <button type="submit">Change the password</button>
</form>
