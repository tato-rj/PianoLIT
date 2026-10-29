@extends('auth.google.layout', ['title' => $linking ? 'Connect your account' : 'Confirm your account'])
@section('content')
@php($provider = $provider ?? 'google')
@php($providerName = app(\App\Auth\Google\Accounts::class)->provider($provider))
<h3>{{ $linking ? 'Your PianoLIT account already exists' : 'Confirm your identity' }}</h3>
<p>{{ $linking ? 'Confirm ownership once to connect '.$providerName.' and keep your profile, membership and saved pieces.' : 'Please confirm your account before changing your sign-in options.' }}</p>
<p class="text-muted text-break">{{ $challenge->account_email }}</p>
@if($hasPassword)
<form method="POST" action="{{ route($provider.'.link.password') }}" disable-on-submit>
  @csrf
  <input type="hidden" name="challenge_id" value="{{ $challenge->id }}">
  @input(['id' => 'existing-password', 'type' => 'password', 'name' => 'password', 'label' => 'PianoLIT password', 'placeholder' => 'Password', 'autocomplete' => 'current-password'])
  <button class="btn btn-primary w-100" type="submit">{{ $linking ? 'Connect '.$providerName : 'Confirm my account' }}</button>
</form>
<div class="text-center text-muted my-3">or</div>
@endif
<form method="POST" action="{{ route($provider.'.link.email') }}" disable-on-submit>
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
@if(!$linking && !empty($apple))
<form method="POST" action="{{ route('apple.reauthenticate') }}" class="mt-3" disable-on-submit>
  @csrf
  <button class="btn btn-secondary d-flex align-items-center justify-content-center gap-2 w-100" type="submit">@include('auth.apple.logo') Confirm with Apple</button>
</form>
@endif
<p class="mt-3 mb-0 text-center"><a href="{{ $linking ? '/login' : '/auth/google/accounts' }}">Cancel</a></p>
@endsection
