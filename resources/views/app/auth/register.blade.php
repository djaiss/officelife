{{--
    Placeholder screen. It carries the fields the controller expects and nothing
    else: no layout, no styling. Replace it with the real design.
--}}
<h1>Create your company</h1>

@if ($errors->any())
  <ul>
    @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </ul>
@endif

<form method="POST" action="{{ route('register') }}">
  @csrf
  <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="Company name">
  <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="First name">
  <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Last name">
  <input type="email" name="email" value="{{ old('email') }}" placeholder="Email">
  <input type="password" name="password" placeholder="Password">
  <input type="password" name="password_confirmation" placeholder="Confirm password">
  <label><input type="checkbox" name="terms"> I agree to the terms of use and the privacy policy</label>
  <button type="submit">Create the company</button>
</form>

<a href="{{ route('login') }}">I already have an account</a>
