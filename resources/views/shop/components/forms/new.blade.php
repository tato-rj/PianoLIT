<form action="{{ $product->purchaseRoute() }}" method="POST" id="payment-form" data-shop-payment="new" data-key="{{ (new \App\Billing\Sources\Concerns\StripeJurisdiction)->set(true) }}" @if($hide ?? false) hidden @endif>
    @csrf
    <input type="hidden" name="payment_method" value="new">
    <input type="hidden" name="coupon" value="" data-coupon-value>
    <div class="form-group mb-3">
        @include('components.forms.label', ['id' => 'card-element', 'labelId' => 'shop-card-label', 'labelTag' => 'span', 'label' => 'Card details'])
        <div id="card-element" class="form-control" role="group" aria-labelledby="shop-card-label" aria-describedby="card-errors"></div>
        <p class="small text-muted mt-2 mb-0" data-card-status role="status">Loading secure card field…</p>
        <p id="card-errors" class="small text-danger mt-2 mb-0" role="alert"></p>
        <button type="button" class="btn btn-secondary btn-sm mt-2" data-card-retry hidden>Reload card field</button>
    </div>
    <div class="d-flex align-items-center gap-2 mb-4">
        @toggle(['name' => 'save_card', 'toggle' => true, 'autoToggle' => false, 'inputValue' => '1', 'ariaLabel' => 'Save this card for future purchases'])
        <span class="small text-muted">Save this card for future purchases</span>
    </div>
    <button type="submit" class="btn btn-primary w-100" data-order-pay disabled>Pay ${{ number_format($product->finalPrice(), 2) }}</button>
</form>
