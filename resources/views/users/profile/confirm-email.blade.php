@extends('layouts.app', ['title' => 'Confirm your email'])
@section('content')
<div class="container py-5">
    <div class="row justify-content-center"><div class="col-md-8 col-lg-6">
        <h1>Confirm your new email</h1>
        <p>Use <strong class="text-break">{{ $email }}</strong> to sign in to PianoLIT and receive your subscribed emails.</p>
        <form method="POST" action="{{ request()->fullUrl() }}" disable-on-submit>
            @csrf
            <button type="submit" class="btn btn-primary">Confirm email address</button>
            <a href="{{ route('users.profile') }}#security" class="btn btn-secondary">Cancel</a>
        </form>
    </div></div>
</div>
@endsection
