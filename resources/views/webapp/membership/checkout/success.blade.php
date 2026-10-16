@extends('webapp.layouts.app', ['title' => 'Welcome to Premium'])

@section('content')
@include('webapp.layouts.header')
<div class="membership membership-welcome">
    @include('webapp.membership.steps', ['step' => 3])
    <section class="membership-welcome-hero" aria-labelledby="welcome-heading">
        <div class="membership-welcome-message">
            <span class="membership-confirmed">@icon('circle-check', ['mr' => 0]) {{ $welcomeTrial ? 'Your free trial is active' : 'Your membership is active' }}</span>
            <h1 id="welcome-heading">Welcome to your<br>next musical chapter.</h1>
            <p>That piece you’ve been meaning to learn? Let’s find it.<br>Your Premium access is ready, and there’s so much to discover.</p>
            <a class="btn btn-primary" href="{{ route('webapp.discover') }}">Start exploring @icon('arrow-right', ['mr' => 0])</a>
        </div>
        <img class="membership-welcome-art" src="{{ asset('images/webapp/collections/melody.webp') }}" alt="" width="900" height="600">
    </section>
    <section class="membership-unlocked" aria-labelledby="membership-unlocked-heading">
        <h2 id="membership-unlocked-heading">A little inspiration. A lot to look forward to.</h2>
        <div class="membership-unlocked-list">
            <span>@icon('headphones') Full recordings & videos</span>
            <span>@icon('book-open') Complete scores & your markings</span>
            <span>@icon('heart') A repertoire that grows with you</span>
        </div>
    </section>
    <section class="membership-first-steps" aria-labelledby="membership-first-steps-heading">
        <div class="membership-section-heading">
            <span class="membership-eyebrow">Make yourself at home</span>
            <h2 id="membership-first-steps-heading">Where will the music take you?</h2>
            <p>Pick a starting point. There’s no wrong first note.</p>
        </div>
        <div class="membership-feature-grid">
            <a class="membership-next-card" href="{{ route('webapp.tour') }}">
                <span class="membership-icon membership-icon--blue" aria-hidden="true">@icon('wand-sparkles', ['mr' => 0])</span>
                <h3>Find your next piece</h3>
                <p>Tell us what you’re in the mood for. We’ll help you find a match.</p>
                <span class="membership-next-link">Find my match @icon('arrow-right', ['mr' => 0])</span>
            </a>
            <a class="membership-next-card" href="{{ route('webapp.playlists') }}">
                <span class="membership-icon membership-icon--green" aria-hidden="true">@icon('layers', ['mr' => 0])</span>
                <h3>Follow your curiosity</h3>
                <p>Step into a curated collection and discover music you might have missed.</p>
                <span class="membership-next-link">Browse collections @icon('arrow-right', ['mr' => 0])</span>
            </a>
            <a class="membership-next-card" href="{{ route('webapp.my-pieces') }}">
                <span class="membership-icon membership-icon--purple" aria-hidden="true">@icon('heart', ['mr' => 0])</span>
                <h3>Make room for favorites</h3>
                <p>Keep the pieces you love close. Your personal repertoire starts here.</p>
                <span class="membership-next-link">Open My pieces @icon('arrow-right', ['mr' => 0])</span>
            </a>
        </div>
    </section>
    <aside class="membership-welcome-receipt">
        <div>
            <strong>PianoLIT Premium{{ $welcomePlan ? ' · '.ucfirst($welcomePlan) : '' }}</strong>
            <p>
                @if($welcomeTrial && $welcomeRenewsAt)
                    No payment today. Your trial ends {{ $welcomeRenewsAt->toFormattedDateString() }}. Cancel before then to avoid the first payment.
                @elseif($welcomeRenewsAt)
                    Your next renewal is {{ $welcomeRenewsAt->toFormattedDateString() }}. Your billing details are in My Membership.
                @else
                    Your plan and billing details are ready in My Membership.
                @endif
            </p>
        </div>
        <a href="{{ route('webapp.membership.edit') }}">Manage membership @icon('arrow-right', ['mr' => 0])</a>
    </aside>
    <p class="membership-help">We’re happy you’re here. <a href="mailto:contact@pianolit.com">Say hello if you need anything.</a></p>
</div>
@endsection
