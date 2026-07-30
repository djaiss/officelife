{{--
    Placeholder screen. It carries the fields the controller expects and nothing
    else: no layout, no styling. Replace it with the real design.
--}}
<h1>Verify your email address</h1>

<p>We sent you a link. Follow it to confirm the address you signed up with.</p>

@if (session('status') === 'verification-link-sent')
  <p>A new link is on its way.</p>
@endif

<form method="POST" action="{{ route('verification.store') }}">
  @csrf
  <button type="submit">Send the link again</button>
</form>

<form method="POST" action="{{ route('logout') }}">
  @csrf
  <button type="submit">Sign out</button>
</form>
