@extends('auth.google.layout', ['title' => 'Verify your email'])
@section('content')
<h3>Finish creating your account</h3>
<p>Confirm this email address to create your PianoLIT account and connect Apple.</p>
<form method="POST" action="{{ request()->fullUrl() }}" disable-on-submit>
  @csrf
  <button class="btn btn-primary w-100" type="submit">Verify email and create my account</button>
</form>
@endsection
