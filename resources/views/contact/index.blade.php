@extends('layouts.app', ['title' => 'Support center | PianoLIT'])

@section('content')
<div class="container support-page py-4 py-md-5">
    @include('components.navigation.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Support'],
    ]])

    <header class="row align-items-end g-3 mb-5">
        <div class="col-lg-9">
            <h1 class="mb-2">Support center</h1>
            <p class="text-muted mb-0">Find your way around PianoLIT and get help when you need it.</p>
        </div>
        <div class="col-lg-3 text-lg-end">
            <a class="btn btn-primary" href="mailto:{{ config('app.emails.general') }}">@icon('mail')Email support</a>
        </div>
    </header>

    <div class="row g-4 g-lg-5">
        <aside class="col-lg-3">
            @include('components.navigation.section-navigation', ['id' => 'support-navigation', 'label' => 'Support topics', 'heading' => 'On this page', 'items' => [
                ['target' => 'start-here', 'label' => 'Start here'],
                ['target' => 'explore', 'label' => 'Explore PianoLIT'],
                ['target' => 'memberships', 'label' => 'Memberships & billing'],
                ['target' => 'account', 'label' => 'Account & sign-in'],
                ['target' => 'using-pianolit', 'label' => 'Using PianoLIT'],
                ['target' => 'purchases', 'label' => 'Shop purchases'],
                ['target' => 'contact-support', 'label' => 'Contact support'],
            ]])
        </aside>
        <div class="col-lg-9">
            <section id="start-here" class="support-section" aria-labelledby="start-heading">
                <h2 id="start-heading">What is PianoLIT?</h2>
                <p>PianoLIT helps pianists, teachers, and music lovers discover repertoire, understand music, and enjoy practicing. Our web and iOS apps bring pieces and practice resources together; our other hubs offer learning tools, reading, and more.</p>
                <div class="row g-3 mt-1">
                    @foreach([
                        ['id' => 'memberships', 'icon' => 'credit-card', 'title' => 'Manage a membership', 'text' => 'Plans, payments & cancellation'],
                        ['id' => 'account', 'icon' => 'user-round', 'title' => 'Get into your account', 'text' => 'Sign-in, password & profile'],
                        ['id' => 'using-pianolit', 'icon' => 'circle-help', 'title' => 'Find an answer', 'text' => 'Playback, scores & saved pieces'],
                    ] as $shortcut)
                        <div class="col-md-4">
                            <a class="card card-lift border rounded h-100 p-3 text-body text-decoration-none" href="#{{ $shortcut['id'] }}">
                                <div class="d-flex justify-content-between align-items-center mb-3">@icon($shortcut['icon'], ['mr' => 0, 'size' => 'lg'])@icon('arrow-up-right', ['mr' => 0, 'classes' => 'text-muted'])</div>
                                <h3 class="h6 mb-1">{{ $shortcut['title'] }}</h3>
                                <p class="small text-muted mb-0">{{ $shortcut['text'] }}</p>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>

            @include('contact.hubs')
            @include('contact.memberships')
            @include('contact.faq')

            <section id="contact-support" class="support-section border-top pt-5" aria-labelledby="contact-heading">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h2 id="contact-heading">Contact support</h2>
                        <p>Still need help? Email us about your account, a payment, a technical issue, or anything else about PianoLIT.</p>
                        <a class="btn btn-primary" href="mailto:{{ config('app.emails.general') }}">@icon('mail')Contact support</a>
                        <p class="small mt-3 mb-0"><a class="text-break" href="mailto:{{ config('app.emails.general') }}">{{ config('app.emails.general') }}</a></p>
                    </div>
                    <div class="col-md-6">
                        <h3 class="h6 mb-3">What to include</h3>
                        <ul class="ps-3 text-muted">
                            <li>Your PianoLIT account email</li>
                            <li>The page, piece, or purchase involved</li>
                            <li>What happened and what you expected</li>
                            <li>Your device, browser or app version, and a screenshot if useful</li>
                        </ul>
                        <p class="small text-muted mb-0">For billing questions, include the purchase date and whether you paid on the website or through Apple. Please leave out passwords, verification codes, and full card details.</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
