@component('components.billing.checkout.modal', [
    'cancelUrl' => $cancelUrl, 'directCheckout' => $directCheckout ?? false,
    'checkoutKey' => $kind === 'digital' ? $productType.':'.$productId : 'merchandise', 'checkoutController' => mix('js/views/merchandise-checkout.js'),
    'checkoutProvider' => $mode === 'demo' ? 'demo' : 'stripe',
])
    @slot('payment')
        <div data-merchandise-checkout data-mode="{{ $mode }}" data-key="{{ $stripeKey }}" data-kind="{{ $kind }}"
            data-quote-url="{{ route('shop.checkout.quote') }}" data-prepare-url="{{ route('shop.checkout.prepare') }}"
            data-complete-url="{{ route('shop.checkout.complete') }}" data-countries="{{ json_encode($countries) }}">
            <form data-shipping-form>
                @csrf
                <input type="hidden" name="kind" value="{{ $kind }}">
                @if($kind === 'digital')<input type="hidden" name="product_type" value="{{ $productType }}"><input type="hidden" name="product_id" value="{{ $productId }}">@endif
                <fieldset class="checkout-shipping">
                    <legend>{{ $kind === 'digital' ? 'Billing details' : 'Contact and delivery' }}</legend>
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">@input(['bag' => 'default', 'id' => 'delivery-email', 'name' => 'email', 'type' => 'email', 'label' => 'Email', 'autocomplete' => 'email', 'value' => optional(auth('web')->user())->email, 'readonly' => $kind === 'digital', 'limit' => 254])</div>
                        <div class="col-12 col-sm-6">@input(['bag' => 'default', 'id' => 'delivery-name', 'name' => 'name', 'label' => 'Full name', 'autocomplete' => 'shipping name', 'value' => optional(auth('web')->user())->full_name, 'limit' => 100])</div>
                        <div class="col-12">@input(['bag' => 'default', 'id' => 'delivery-address', 'name' => 'address1', 'label' => 'Street address', 'autocomplete' => 'shipping address-line1', 'limit' => 150])</div>
                        <div class="col-12">@input(['bag' => 'default', 'id' => 'delivery-address2', 'name' => 'address2', 'label' => 'Apartment, suite, etc. (optional)', 'autocomplete' => 'shipping address-line2', 'required' => false, 'limit' => 150])</div>
                        <div class="col-12 col-sm-6">@select(['bag' => 'default', 'id' => 'delivery-country', 'name' => 'country_code', 'label' => 'Country', 'choices' => ['' => 'Choose country'] + collect($countries)->pluck('name', 'code')->all()])</div>
                        <div class="col-12 col-sm-6" data-state-field>
                            @if($kind === 'digital')
                                @input(['bag' => 'default', 'id' => 'delivery-state', 'name' => 'state_code', 'label' => 'State / province', 'autocomplete' => 'billing address-level1', 'required' => false, 'limit' => 32])
                            @else
                                @select(['bag' => 'default', 'id' => 'delivery-state', 'name' => 'state_code', 'label' => 'State / province', 'choices' => []])
                            @endif
                        </div>
                        <div class="col-12 col-sm-6">@input(['bag' => 'default', 'id' => 'delivery-city', 'name' => 'city', 'label' => 'City', 'autocomplete' => 'shipping address-level2', 'limit' => 100])</div>
                        <div class="col-12 col-sm-6">@input(['bag' => 'default', 'id' => 'delivery-zip', 'name' => 'zip', 'label' => 'ZIP / postal code', 'autocomplete' => 'shipping postal-code', 'required' => false, 'limit' => 20])</div>
                    </div>
                    @if($kind === 'digital')
                        <details class="membership-coupon"><summary>Have a coupon code? @icon('plus', ['mr' => 0])</summary>
                            <div class="mt-3">@input(['bag' => 'default', 'id' => 'order-coupon', 'name' => 'coupon', 'label' => 'Coupon code', 'required' => false, 'limit' => 80, 'placeholder' => 'Enter code'])</div>
                        </details>
                    @else
                        <div class="mt-4" data-shipping-options hidden>@select(['bag' => 'default', 'id' => 'delivery-method', 'name' => 'shipping_id', 'label' => 'Delivery method', 'choices' => [], 'required' => false])</div>
                    @endif
                    <p class="mt-3 mb-0 text-muted" data-delivery-status role="status" aria-live="polite">{{ $kind === 'digital' ? 'Your total updates automatically when your billing address is complete.' : 'Delivery and tax update automatically when your address is complete.' }}</p>
                    <button type="button" class="btn btn-secondary mt-3" data-delivery-retry hidden>Try again</button>
                </fieldset>
            </form>
            <form data-checkout-payment data-merchandise-payment data-wallet-controller="{{ mix('js/components/express-checkout.js') }}">
                @csrf
                @if($mode !== 'demo')
                    @include('components.billing.checkout.wallets')
                    <div class="checkout-card-and-fields">
                        @include('components.billing.payment-card-preview')
                        @include('components.billing.payment-card-fields', ['prefix' => 'merchandise', 'cardholder' => optional(auth('web')->user())->full_name])
                    </div>
                    <p class="membership-card-status" data-card-status role="status">Loading secure card fields…</p>
                @endif
                <p class="membership-card-errors" data-checkout-error role="alert" tabindex="-1"></p>
                <a href="#" data-checkout-order-link hidden>Check order status</a>
                <p class="membership-terms">By placing your order, you agree to the <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">Terms of Service</a>.</p>
                <div class="checkout-actions">
                    <button type="submit" class="btn btn-primary" disabled>@icon('lock') <span data-pay-label>{{ $mode === 'demo' ? 'Complete test order' : 'Pay securely' }}</span></button>
                    <a href="{{ $cancelUrl }}" class="checkout-cancel" data-checkout-cancel>Cancel and return</a>
                </div>
                <p class="membership-stripe-credit">{{ $mode === 'demo' ? 'Offline demonstration' : 'Payments securely processed by Stripe' }}</p>
            </form>
        </div>
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
            <div data-checkout-discount-line hidden><span>Coupon discount</span><strong data-checkout-discount></strong></div>
            @if($kind !== 'digital')<div><span>Delivery</span><strong data-checkout-shipping>Calculated at checkout</strong></div>@endif
            <div><span>Tax</span><strong data-checkout-tax>Calculated at checkout</strong></div>
            <div class="membership-due"><span>Total <small>(USD)</small></span><strong data-checkout-total>—</strong></div>
        </div>
        <p class="membership-summary-note">{{ $kind === 'digital' ? 'Your download will be saved to your PianoLIT account after payment. No shipping is needed.' : 'Review your total before paying. Your items will be printed and shipped to your delivery address.' }}</p>
        <p class="checkout-summary-help">Need help? <a href="mailto:contact@pianolit.com">Contact us</a></p>
    @endslot
@endcomponent
