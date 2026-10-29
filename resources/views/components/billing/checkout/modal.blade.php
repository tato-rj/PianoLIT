@component('components.dialogs.fullscreen.panel', ['id' => 'payment-checkout-modal', 'headingId' => 'checkout-loading-heading', 'classes' => 'checkout-modal', 'data' => [
    'checkout-modal' => 'true', 'checkout-direct' => !empty($directCheckout) ? 'true' : 'false',
    'checkout-state' => 'loading',
    'checkout-key' => $checkoutKey ?? '', 'checkout-controller' => $checkoutController ?? '',
    'checkout-provider' => $checkoutProvider ?? 'stripe',
    'checkout-auto-url' => $checkoutUrl ?? '', 'checkout-cancel-url' => $cancelUrl
], 'floatingClose' => true, 'closeLabel' => 'Close checkout', 'closeData' => ['checkout-cancel' => 'true']])
    @include('components.billing.checkout.loading')
    <div class="checkout-stage" data-checkout-stage aria-hidden="true" inert>
    <div class="checkout-scroll" data-checkout-surface>
        <div class="checkout-layout">
            <aside class="checkout-summary" aria-labelledby="checkout-summary-heading">
                <header class="checkout-brand">
                    <a href="{{ $cancelUrl }}" data-checkout-cancel aria-label="Return to PianoLIT">@brandIcon</a>
                </header>
                <h2 id="checkout-summary-heading">Order summary</h2>
                {{ $summary }}
            </aside>
            <section class="checkout-payment" aria-labelledby="checkout-heading">
                <div class="checkout-payment-heading">
                    <div>
                        <h1 id="checkout-heading">{{ $checkoutTitle ?? 'Payment details' }}</h1>
                        {{ $account ?? '' }}
                    </div>
                </div>
                {{ $payment }}
            </section>
        </div>
    </div>
    </div>
@endcomponent
