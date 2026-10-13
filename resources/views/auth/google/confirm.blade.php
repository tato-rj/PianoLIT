@extends('auth.google.layout', ['title' => $linking ? 'Connect your account' : 'Confirm your account'])
@section('content')
<h3>{{ $linking ? 'Your PianoLIT account already exists' : 'Confirm your identity' }}</h3>
<p>{{ $linking ? 'Confirm ownership once to connect Google and keep your profile, membership and saved pieces.' : 'Please confirm your account before changing your sign-in options.' }}</p>
<p class="text-muted text-break">{{ $challenge->account_email }}</p>
@if($hasPassword)
<form method="POST" action="{{ route('google.link.password') }}" disable-on-submit>
  @csrf
  <input type="hidden" name="challenge_id" value="{{ $challenge->id }}">
  <label class="form-label" for="existing-password">PianoLIT password</label>
  <input id="existing-password" class="form-control input-light mb-3" type="password" name="password" autocomplete="current-password" required>
  <button class="btn btn-primary w-100" type="submit">{{ $linking ? 'Connect Google' : 'Confirm my account' }}</button>
</form>
<div class="text-center text-muted my-3">or</div>
@endif
<form method="POST" action="{{ route('google.link.email') }}" disable-on-submit>
  @csrf
  <input type="hidden" name="challenge_id" value="{{ $challenge->id }}">
  <button class="btn btn-secondary w-100" type="submit">Send me a confirmation email</button>
</form>
@if(!$linking && !empty($google))
<form method="POST" action="{{ route('google.reauthenticate') }}" class="mt-3" disable-on-submit>
  @csrf
  <button class="btn btn-secondary d-flex align-items-center justify-content-center gap-2 w-100" type="submit">@include('auth.google.logo') Confirm with Google</button>
</form>
@endif
<p class="mt-3 mb-0 text-center"><a href="{{ $linking ? '/login' : '/auth/google/accounts' }}">Cancel</a></p>
@endsection
