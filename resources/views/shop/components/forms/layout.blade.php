<section aria-labelledby="shop-payment-title">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h2 class="h5 mb-0" id="shop-payment-title">Payment</h2>
        <span class="text-muted small">@icon('lock')Secured by Stripe</span>
    </div>
    {{ $slot }}
    <p class="small text-muted mt-3 mb-0">By placing your order, you agree to our <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">Terms of Service</a>.</p>
</section>
