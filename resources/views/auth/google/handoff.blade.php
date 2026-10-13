@extends('auth.google.layout', ['title' => 'Continue to PianoLIT', 'referrerPolicy' => 'origin'])
@section('content')
<h3>Continue to PianoLIT</h3>
<p>Finishing your sign-in…</p>
<form id="google-handoff" method="POST" action="{{ $target }}">
  <input type="hidden" name="handoff_token" value="{{ $token }}">
  <button class="btn btn-primary w-100" type="submit">Continue</button>
</form>
@endsection
@push('scripts')<script>document.getElementById('google-handoff').submit();</script>@endpush
