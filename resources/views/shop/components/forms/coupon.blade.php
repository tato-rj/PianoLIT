<div class="mt-4 pt-3 border-top" data-shop-coupon data-url="{{ route('shop.validate-coupon') }}">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#shop-promo-code" aria-expanded="false" aria-controls="shop-promo-code">Have a promo code?</button>
    <div class="collapse mt-3" id="shop-promo-code">
        @input(['bag' => 'default', 'id' => 'checkout-coupon', 'name' => 'coupon', 'label' => 'Promo code', 'placeholder' => 'Enter your code', 'limit' => 20, 'required' => false, 'autocomplete' => 'off'])
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-secondary btn-sm" data-coupon-apply>Apply code</button>
            <button type="button" class="btn btn-secondary btn-sm" data-coupon-remove hidden>Remove</button>
            <span class="small text-muted" data-coupon-loading role="status" hidden>Checking code…</span>
        </div>
    </div>
    <p class="small mt-2 mb-0" data-coupon-feedback role="status" aria-live="polite"></p>
</div>
