@extends('auth.google.layout', ['title' => 'Confirm your account'])
@section('content')
<h3>Confirm your PianoLIT account</h3>
<p>{{ $challenge->purpose === 'link' ? 'Connect this '.app(\App\Auth\Google\Accounts::class)->provider($provider ?? 'google').' account to your existing PianoLIT profile.' : 'Confirm your identity to update your sign-in options.' }}</p>
<form method="POST" action="{{ request()->fullUrl() }}" disable-on-submit>
  @csrf
  <button class="btn btn-primary w-100" type="submit">Confirm my account</button>
</form>
@endsection
