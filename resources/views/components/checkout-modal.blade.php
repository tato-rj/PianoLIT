@component('components.fullscreen-modal', ['id' => 'payment-checkout-modal', 'headingId' => 'checkout-loading-heading', 'classes' => 'checkout-modal', 'data' => [
    'checkout-modal' => 'true', 'checkout-direct' => !empty($directCheckout) ? 'true' : 'false',
    'checkout-state' => 'loading',
    'checkout-key' => $checkoutKey ?? '', 'checkout-controller' => $checkoutController ?? '',
    'checkout-auto-url' => $checkoutUrl ?? '', 'checkout-cancel-url' => $cancelUrl
]])
    @include('components.checkout-loading')
    <div class="checkout-stage" data-checkout-stage aria-hidden="true" inert>
    <button type="button" class="checkout-cancel checkout-top-cancel" data-checkout-cancel aria-label="Close checkout">@icon('x', ['mr' => 0])</button>
    <div class="checkout-scroll" data-checkout-surface>
        <div class="checkout-layout">
            <section class="checkout-payment" aria-labelledby="checkout-heading">
                <header class="checkout-brand">
                    <a href="{{ $cancelUrl }}" data-checkout-cancel aria-label="Return to PianoLIT">@brandIcon</a>
                </header>
                <div class="checkout-payment-heading">
                    <div>
                        <h1 id="checkout-heading">{{ $checkoutTitle ?? 'Payment details' }}</h1>
                        {{ $account ?? '' }}
                    </div>
                    <span class="checkout-security">@icon('lock', ['mr' => 0])<span><strong>Secure card payment</strong><small>Processed by Stripe</small></span></span>
                </div>
                {{ $payment }}
            </section>
            <aside class="checkout-summary" aria-labelledby="checkout-summary-heading">
                <h2 id="checkout-summary-heading">Order summary</h2>
                {{ $summary }}
            </aside>
        </div>
    </div>
    </div>
@endcomponent
