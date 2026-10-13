@extends('auth.google.layout', ['title' => 'Set a password'])
@section('content')
<h3>Set a PianoLIT password</h3>
<p>Use your email and this password to sign in on the website or in the existing PianoLIT iOS app.</p>
<p class="text-muted text-break">Your PianoLIT email: {{ auth('web')->user()->email }}</p>
<form method="POST" action="{{ route('google.password.set') }}" disable-on-submit>
  @csrf
  <label for="new-password" class="form-label">Password</label>
  <input id="new-password" class="form-control input-light mb-3" type="password" name="password" minlength="8" autocomplete="new-password" required>
  <label for="confirm-password" class="form-label">Confirm password</label>
  <input id="confirm-password" class="form-control input-light mb-3" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required>
  <button class="btn btn-primary w-100" type="submit">Set my password</button>
</form>
<p class="mt-3 mb-0 text-center"><a href="/auth/google/accounts">Cancel</a></p>
@endsection
