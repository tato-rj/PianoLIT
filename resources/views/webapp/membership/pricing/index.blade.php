@extends('webapp.layouts.app', ['title' => 'PianoLIT Premium'])

@section('content')
@include('webapp.layouts.header')

<div class="membership membership-pricing">
    {{-- @include('webapp.membership.steps', ['step' => 1]) --}}
    <header class="membership-heading">
        <span class="membership-eyebrow">PianoLIT Premium</span>
        <h1>Find your next piece.</h1>
        <p>Explore piano music with scores, videos, and recordings, all in one place.</p>
    </header>

    @include('webapp.membership.pricing.plans')
    @include('webapp.membership.pricing.faq')

    <aside class="membership-institution">
        <span class="membership-icon" aria-hidden="true">@icon('graduation-cap', ['mr' => 0])</span>
        <div>
            <h2>Bring PianoLIT to your studio or school</h2>
            <p>Inspiration is even better together. Ask us about institutional memberships.</p>
        </div>
        <a class="btn btn-secondary" href="mailto:contact@pianolit.com?subject=Institutional%20pricing%20inquiry">Let’s talk @icon('arrow-right', ['mr' => 0])</a>
    </aside>
    <p class="membership-help">Have a question? <a href="mailto:contact@pianolit.com">We’re here to help.</a></p>
</div>
@endsection
