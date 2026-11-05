@php
    $shopWelcomeSubscribed = session('newsletter_subscribed', false) || session()->has('shop_newsletter_email')
        || (auth('web')->check() && app(\App\Services\Newsletter\Subscribers::class)->subscribed(auth('web')->user()->email));
    $shopWelcomeOfferAvailable = !$shopWelcomeSubscribed && app(\App\Services\Shop\CheckoutEnvironment::class)->available()
        && app(\App\Services\Shop\CheckoutEnvironment::class)->available('digital');
@endphp
<div data-shop-welcome-state data-subscribed="{{ $shopWelcomeSubscribed ? 'true' : 'false' }}">
    @unless($shopWelcomeSubscribed)
        @include('shop.storefront.components.welcome-launcher', ['offerAvailable' => $shopWelcomeOfferAvailable])
        @include('components.promotions.popups.newsletter-subscription', [
            'formId' => 'shop-newsletter-subscription-form',
            'newsletterErrorBag' => 'storefront',
            'newsletterHeading' => $shopWelcomeOfferAvailable ? '10% off your first order' : null,
            'newsletterDescription' => $shopWelcomeOfferAvailable ? 'Join the PianoLIT newsletter for new music, tools, stories, and more in one monthly email, plus 10% off your first Shop order.' : null,
            'newsletterButtonLabel' => $shopWelcomeOfferAvailable ? 'Get my 10% discount' : null,
            'newsletterEmail' => optional(auth('web')->user())->email,
            'newsletterOfferTerms' => $shopWelcomeOfferAvailable ? 'Applied automatically at checkout with the subscribed email. First order only, including eBooks and eScores. Excludes delivery and tax. Cannot be combined with other coupons.' : null,
            'newsletterModalData' => [
                'shop-welcome' => 'true', 'shop-welcome-auto' => request()->routeIs('shop.home') ? 'true' : 'false',
                'shop-welcome-retry' => old('form_id') === 'shop-newsletter-subscription-form' && $errors->getBag('storefront')->any() ? 'true' : 'false',
            ],
        ])
    @endunless
</div>
