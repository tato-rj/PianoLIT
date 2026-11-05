@php
    $shopWelcomeAuthenticated = auth('web')->check();
    $shopWelcomeSubscribed = session('newsletter_subscribed', false) || session()->has('shop_newsletter_email')
        || ($shopWelcomeAuthenticated && app(\App\Services\Newsletter\Subscribers::class)->subscribed(auth('web')->user()->email));
@endphp
<div data-shop-welcome-state data-authenticated="{{ $shopWelcomeAuthenticated ? 'true' : 'false' }}" data-subscribed="{{ $shopWelcomeSubscribed ? 'true' : 'false' }}">
    @unless($shopWelcomeAuthenticated || $shopWelcomeSubscribed)
        @include('shop.storefront.components.welcome-launcher')
        @include('components.promotions.popups.newsletter-subscription', [
            'formId' => 'shop-newsletter-subscription-form',
            'newsletterErrorBag' => 'storefront',
            'newsletterHeading' => '10% off your first order',
            'newsletterDescription' => 'Join the PianoLIT newsletter for new music, tools, stories, and more in one monthly email, plus 10% off your first Shop order.',
            'newsletterButtonLabel' => 'Get my 10% discount',
            'newsletterOfferTerms' => 'Applied automatically at checkout with the subscribed email. First order only, including eBooks and eScores. Excludes delivery and tax. Cannot be combined with other coupons.',
            'newsletterModalData' => [
                'shop-welcome' => 'true', 'shop-welcome-auto' => request()->routeIs('shop.home') ? 'true' : 'false',
                'shop-welcome-retry' => old('form_id') === 'shop-newsletter-subscription-form' && $errors->getBag('storefront')->any() ? 'true' : 'false',
            ],
        ])
    @endunless
</div>
