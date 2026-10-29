@component('components.dialogs.fullscreen.panel', ['id' => 'payment-checkout-modal', 'headingId' => 'checkout-loading-heading', 'classes' => 'checkout-modal', 'floatingClose' => true, 'closeLabel' => 'Close checkout', 'closeData' => ['checkout-cancel' => 'true'], 'data' => ['checkout-modal' => 'true', 'checkout-state' => 'loading', 'checkout-auto-url' => $checkoutUrl ?? '', 'checkout-cancel-url' => $cancelUrl ?? route('webapp.membership.pricing')]])
    @include('components.billing.checkout.loading')
@endcomponent
