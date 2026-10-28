<div class="shop-order-summary p-4">
    <h2 class="h5 mb-4">Order summary</h2>
    @include('shop.components.order-item')
    @unless($existingPurchase)
    <dl class="shop-order-summary__totals mt-4 mb-0">
        <div class="d-flex justify-content-between gap-3 mb-2"><dt class="fw-normal text-muted">Subtotal</dt><dd class="mb-0">${{ number_format($product->finalPrice(), 2) }}</dd></div>
        @if($membershipDownload)
        <div class="d-flex justify-content-between gap-3 mb-2"><dt class="fw-normal text-muted">Membership download</dt><dd class="mb-0">−${{ number_format($product->finalPrice(), 2) }}</dd></div>
        @endif
        <div data-order-discount-row hidden><dt class="fw-normal text-muted">Promo code</dt><dd class="mb-0 text-success" data-order-discount></dd></div>
        <div class="d-flex justify-content-between align-items-baseline gap-3 border-top pt-3 mt-3"><dt>Total today <span class="fw-normal text-muted small">USD</span></dt><dd class="h5 mb-0" data-order-total>${{ number_format($totalToday / 100, 2) }}</dd></div>
    </dl>
    @if($needsPayment)
        @include('shop.components.forms.coupon')
        <p class="small text-muted mt-3 mb-0">One-time payment. No subscription.</p>
    @else
        <p class="small text-muted mt-3 mb-0">No charge for this download.</p>
    @endif
    @endunless
</div>
<p class="small text-muted mt-3 mb-0">@icon('circle-help')Need a hand? <a href="mailto:{{ config('app.emails.general') }}?subject=Help%20with%20a%20purchase">Contact us</a></p>
