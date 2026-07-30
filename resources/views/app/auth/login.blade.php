{{--
    Placeholder screen. It carries the fields the controller expects and nothing
    else: no layout, no styling. Replace it with the real design.
--}}
<h1>Sign in</h1>

@if ($errors->any())
  <ul>
    @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </ul>
@endif

<form method="POST" action="{{ route('login') }}">
  @csrf
  <input type="email" name="email" value="{{ old('email') }}" placeholder="Email">
  <input type="password" name="password" placeholder="Password">
  <label><input type="checkbox" name="remember"> Remember me</label>
  <button type="submit">Sign in</button>
</form>

<a href="{{ route('password.request') }}">Forgot your password?</a>
<a href="{{ route('magic.link') }}">Send me a magic link</a>
<a href="{{ route('register') }}">Create an account</a>
