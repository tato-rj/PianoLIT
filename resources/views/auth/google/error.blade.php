@extends('auth.google.layout', ['title' => 'Account verification'])
@section('content')
<h3>Let’s try that again</h3>
<p>{{ $message }}</p>
<a class="btn btn-primary w-100" href="{{ auth('web')->check() ? '/auth/google/accounts' : '/login' }}">{{ auth('web')->check() ? 'Back to connected accounts' : 'Back to sign in' }}</a>
@endsection
