<?php
/**
 * @var string|null $error
 */
?>
{{--
    Placeholder screen. It carries the fields the controller expects and nothing
    else: no layout, no styling. Replace it with the real design.
--}}
<h1>Two factor authentication</h1>

@isset($error)
  <p>{{ $error }}</p>
@endisset

@if ($errors->any())
  <ul>
    @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </ul>
@endif

<form method="POST" action="{{ route('2fa.challenge.store') }}">
  @csrf
  <input type="text" name="code" placeholder="Code from your app, or a recovery code">
  <button type="submit">Continue</button>
</form>
