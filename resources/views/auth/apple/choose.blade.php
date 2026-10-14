@extends('auth.google.layout', ['title' => 'Continue with Apple'])
@section('content')
<h3>Welcome to PianoLIT</h3>
<p>Keep your existing profile and saved music, or create a new account.</p>
@if($choice['identity']['email'])<p class="text-muted text-break">Apple email: {{ $choice['identity']['email'] }}</p>@endif
<section class="border rounded p-3 mb-3">
  <h5>I already have a PianoLIT account</h5>
  <p class="small text-muted">Enter your PianoLIT email. We’ll confirm ownership before connecting Apple, including when you use Hide My Email.</p>
  <form method="POST" action="{{ route('apple.existing') }}" disable-on-submit>
    @csrf <input type="hidden" name="choice_id" value="{{ $choice['id'] }}">
    <label for="existing-email" class="form-label">PianoLIT email</label>
    <input id="existing-email" type="email" name="email" class="form-control input-light mb-3" autocomplete="email" required maxlength="255">
    <button type="submit" class="btn btn-secondary w-100">Connect my existing account</button>
  </form>
</section>
@if($choice['identity']['email'])
<form method="POST" action="{{ route('apple.register') }}" disable-on-submit>
  @csrf <input type="hidden" name="choice_id" value="{{ $choice['id'] }}">
  <button type="submit" class="btn btn-primary w-100">Create a new PianoLIT account</button>
</form>
@else
<h5>Create a new account</h5>
<p class="small text-muted">Apple didn’t provide an email this time. Verify an email address to finish creating your account.</p>
<form method="POST" action="{{ route('apple.signup.email') }}" disable-on-submit>
  @csrf <input type="hidden" name="choice_id" value="{{ $choice['id'] }}">
  <label for="signup-email" class="form-label">Your email</label>
  <input id="signup-email" type="email" name="email" class="form-control input-light mb-3" autocomplete="email" required maxlength="255">
  <button type="submit" class="btn btn-primary w-100">Verify my email</button>
</form>
@endif
<p class="small text-muted text-center mt-3 mb-0">By continuing, you agree to our <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">Terms</a> and <a href="{{ route('privacy') }}" target="_blank" rel="noopener noreferrer">Privacy Policy</a>.</p>
<p class="text-center mt-3 mb-0"><a href="/login">Cancel</a></p>
@endsection
