{{--
    Placeholder screen. It carries the fields the controller expects and nothing
    else: no layout, no styling. Replace it with the real design.
--}}
<h1>Reset your password</h1>

@if (session('status'))
  <p>{{ session('status') }}</p>
@endif

@if ($errors->any())
  <ul>
    @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </ul>
@endif

<form method="POST" action="{{ route('password.email') }}">
  @csrf
  <input type="email" name="email" value="{{ old('email') }}" placeholder="Email">
  <button type="submit">Send the reset link</button>
</form>

<a href="{{ route('login') }}">Back to sign in</a>
