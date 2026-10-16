<div class="checkout-loading" data-checkout-loading>
    <div class="checkout-scroll">
        <div class="checkout-layout">
            <section class="checkout-payment" aria-labelledby="checkout-loading-heading">
                <header class="checkout-brand">
                    <a href="{{ $cancelUrl ?? route('webapp.membership.pricing') }}" data-checkout-cancel aria-label="Return to PianoLIT">@brandIcon</a>
                </header>
                <div class="checkout-payment-heading">
                    <div>
                        <h1 id="checkout-loading-heading">Secure checkout</h1>
                        <p class="membership-account checkout-loading-status" role="status"><span class="checkout-loading-spinner" aria-hidden="true"></span><span data-checkout-load-status>Preparing your secure payment form…</span></p>
                        <button type="button" class="btn btn-secondary mt-3" data-checkout-retry hidden>Try again</button>
                    </div>
                </div>
                <div aria-hidden="true">
                    <div class="checkout-card-and-fields">
                        <div class="checkout-card-preview checkout-placeholder"></div>
                        <div>
                            @foreach(['Card number', 'Name on card'] as $label)
                            <div class="membership-field"><span class="checkout-placeholder-label">{{ $label }}</span><div class="form-control checkout-placeholder"></div></div>
                            @endforeach
                            <div class="membership-card-row">
                                <div class="membership-field"><span class="checkout-placeholder-label">Expiration date</span><div class="form-control checkout-placeholder"></div></div>
                                <div class="membership-field"><span class="checkout-placeholder-label">Security code</span><div class="form-control checkout-placeholder"></div></div>
                            </div>
                            <div class="membership-field"><span class="checkout-placeholder-label">Billing ZIP / postal code</span><div class="form-control checkout-placeholder"></div></div>
                        </div>
                    </div>
                    <div class="checkout-placeholder-notes">
                        <span class="checkout-placeholder"></span><span class="checkout-placeholder"></span><span class="checkout-placeholder"></span>
                    </div>
                </div>
                <div class="checkout-actions"><span class="btn btn-primary disabled checkout-placeholder-action" aria-hidden="true">Preparing checkout</span><button type="button" class="checkout-cancel" data-checkout-cancel>Cancel and return</button></div>
            </section>
            <aside class="checkout-summary" aria-hidden="true">
                <h2 class="d-flex align-items-center gap-2">Order summary <span class="checkout-loading-spinner" aria-hidden="true"></span></h2>
                <div class="checkout-order-item">
                    <span class="membership-eyebrow checkout-placeholder-text">Your order</span>
                    <h3><span class="checkout-placeholder-text">Order details</span></h3>
                    <p class="checkout-placeholder-copy"><span class="checkout-placeholder"></span><span class="checkout-placeholder"></span><span class="checkout-placeholder"></span></p>
                    <span class="checkout-placeholder-text checkout-placeholder-link">Order information</span>
                </div>
                <div class="membership-order-lines">
                    <div><span class="checkout-placeholder-text">Order details</span><strong class="checkout-placeholder-text">Details</strong></div>
                    <div><span class="checkout-placeholder-text">Billing details</span><strong class="checkout-placeholder-text">Details</strong></div>
                    <div class="membership-due"><span class="checkout-placeholder-text">Due today</span><strong class="checkout-placeholder-text">Total</strong></div>
                </div>
                <p class="membership-summary-note checkout-placeholder-copy checkout-placeholder-copy--short"><span class="checkout-placeholder"></span><span class="checkout-placeholder"></span></p>
            </aside>
        </div>
    </div>
</div>
