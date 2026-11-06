@component('components.billing.checkout.modal', [
    'cancelUrl' => $cancelUrl, 'directCheckout' => $directCheckout ?? false,
    'checkoutKey' => $kind === 'digital' ? $productType.':'.$productId : 'merchandise', 'checkoutController' => mix('js/views/merchandise-checkout.js'),
    'checkoutProvider' => $mode === 'demo' ? 'demo' : 'stripe',
    'checkoutClasses' => $kind === 'merchandise' ? 'checkout-modal--shop' : '',
    'collapsibleSummary' => $kind === 'merchandise', 'summaryTotal' => app(\App\Services\Shop\Storefront::class)->money($snapshot['subtotal'], 'USD'),
])
    @slot('payment')
        @include('shop.storefront.checkout.payment')
    @endslot
    @slot('summary')
        @foreach($snapshot['items'] as $item)
            <div class="checkout-order-item checkout-line">
                @if($item['image'])<img src="{{ $item['image'] }}" alt="" width="64" height="64">@endif
                <div><h3>{{ $item['name'] }}</h3><p class="mb-2">{{ $item['option'] }} · Quantity {{ $item['quantity'] }}</p><strong>{{ app(\App\Services\Shop\Storefront::class)->money($item['unit_price'] * $item['quantity'], 'USD') }}</strong></div>
            </div>
        @endforeach
        <div class="membership-order-lines">
            <div><span>Products</span><strong data-checkout-subtotal>{{ app(\App\Services\Shop\Storefront::class)->money($snapshot['subtotal'], 'USD') }}</strong></div>
            <div data-checkout-discount-line hidden><span data-checkout-discount-label>Discount</span><strong data-checkout-discount></strong></div>
            @if($kind !== 'digital')<div><span>Delivery</span><strong data-checkout-shipping>Calculated at checkout</strong></div>@endif
            <div><span>Tax</span><strong data-checkout-tax>Calculated at checkout</strong></div>
            <div class="membership-due"><span>Total <small>(USD)</small></span><strong data-checkout-total>—</strong></div>
        </div>
        <p class="membership-summary-note">{{ $kind === 'digital' ? 'Your download will be saved to your PianoLIT account after payment. No shipping is needed.' : 'Review your total before paying. Your items will be printed and shipped to your delivery address.' }}</p>
        <p class="checkout-summary-help">Need help? <a href="mailto:contact@pianolit.com">Contact us</a></p>
    @endslot
@endcomponent
