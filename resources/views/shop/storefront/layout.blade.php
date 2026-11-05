@extends('layouts.app', ['title' => $shopTitle ?? 'PianoLIT Shop', 'projectHome' => true, 'bodyClass' => 'shop-page'])

@push('header')
    <link rel="stylesheet" href="{{ mix('css/storefront.css') }}">
@endpush

@section('content')
<div class="shop-storefront d-flex flex-column min-vh-100" data-storefront>
    @include('shop.storefront.components.header')
    <div class="shop-content flex-grow-1">
        @component('components.layout.public.content')
            <div class="container py-4 pb-5">
                @if($errors->getBag('storefront')->any())
                    @alert(['color' => 'red', 'message' => '<strong class="me-2">Sorry | </strong>'.e($errors->getBag('storefront')->first()), 'dismissible' => true, 'floating' => 'top'])
                @endif
                @yield('shop-content')
            </div>
        @endcomponent
    </div>
    @include('components.layout.public.footer')
    @include('shop.storefront.components.alerts')
</div>
@if(request()->routeIs('shop.home'))
    @php($shopWelcomeOfferAvailable = app(\App\Services\Shop\CheckoutEnvironment::class)->available() && app(\App\Services\Shop\CheckoutEnvironment::class)->available('digital'))
    @include('components.promotions.popups.newsletter-subscription', [
        'formId' => 'shop-newsletter-subscription-form',
        'newsletterErrorBag' => 'storefront',
        'newsletterHeading' => $shopWelcomeOfferAvailable ? '10% off your first order' : null,
        'newsletterDescription' => $shopWelcomeOfferAvailable ? 'Join the PianoLIT newsletter for new music, tools, stories, and more in one monthly email, plus 10% off your first Shop order.' : null,
        'newsletterButtonLabel' => $shopWelcomeOfferAvailable ? 'Get my 10% discount' : null,
        'newsletterEmail' => optional(auth('web')->user())->email,
        'newsletterOfferTerms' => $shopWelcomeOfferAvailable ? 'Applied automatically at checkout with the subscribed email. First order only, including eBooks and eScores. Excludes delivery and tax. Cannot be combined with other coupons.' : null,
        'newsletterModalData' => [
            'shop-welcome' => 'true',
            'shop-welcome-retry' => old('form_id') === 'shop-newsletter-subscription-form' && $errors->getBag('storefront')->any() ? 'true' : 'false',
        ],
    ])
@endif
@include('components.billing.checkout.host', ['cancelUrl' => route('shop.bag')])
@endsection

@push('scripts')
    <script src="{{ mix('js/views/storefront.js') }}" defer></script>
    @if(request()->routeIs('shop.home'))
        <script src="{{ mix('js/views/shop-welcome.js') }}" defer></script>
    @endif
@endpush
