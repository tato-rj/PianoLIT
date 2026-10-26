@extends('layouts.raw', ['title' => 'Unsubscribed | PianoLIT'])

@push('header')
<meta name="robots" content="noindex, nofollow">
<style>
.unsubscribe-confirmation {
    min-height: 100vh;
    min-height: 100dvh;
}

.unsubscribe-confirmation__message {
    max-width: 26rem;
    text-wrap: balance;
}

.unsubscribe-confirmation__logo {
    border-radius: 20%;
}
</style>
@endpush

@section('content')
<main class="unsubscribe-confirmation container d-flex align-items-center justify-content-center text-center py-5" aria-labelledby="unsubscribe-heading">
    <div class="unsubscribe-confirmation__message px-3">
        <img src="{{ asset(mix('images/brand/app-icon.svg')) }}" width="72" height="72" alt="PianoLIT" class="unsubscribe-confirmation__logo d-block mx-auto mb-4">
        <h1 id="unsubscribe-heading" class="h4 mb-3">You’re unsubscribed</h1>
        <p class="text-muted mb-0">Your email has been removed from this list.</p>
    </div>
</main>
@endsection
