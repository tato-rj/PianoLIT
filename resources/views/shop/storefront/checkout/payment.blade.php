        @php($walletOnly = $walletOnly ?? false)
        @php($recipient = $recipient ?? [])
        @php($collapsible = $kind === 'merchandise' && !$walletOnly)
        <div data-merchandise-checkout data-wallet-only="{{ $walletOnly ? 'true' : 'false' }}" data-mode="{{ $mode }}" data-key="{{ $stripeKey }}" data-kind="{{ $kind }}"
            data-checkout-initial-section="{{ auth('web')->check() ? 'checkout-delivery' : 'checkout-contact' }}"
            data-quote-url="{{ route('shop.checkout.quote') }}" data-prepare-url="{{ route('shop.checkout.prepare') }}"
            data-complete-url="{{ route('shop.checkout.complete') }}" data-countries="{{ json_encode($countries) }}"
            data-wallet-quote-url="{{ route('shop.checkout.wallet-quote') }}" data-wallet-amount="{{ $snapshot['subtotal'] }}">
            @if($kind === 'merchandise' && $mode !== 'demo')
                @include('components.billing.checkout.wallets', ['divider' => $walletOnly ? '' : 'Or'])
            @endif
            @if($collapsible)<div id="checkout-sections" data-checkout-sections>@endif
            <form data-shipping-form @if($walletOnly) hidden @endif>
                @csrf
                <input type="hidden" name="kind" value="{{ $kind }}">
                @if($kind === 'digital')<input type="hidden" name="product_type" value="{{ $productType }}"><input type="hidden" name="product_id" value="{{ $productId }}">@endif
                @if($kind === 'merchandise')
                    @component('shop.storefront.checkout.section', ['collapsible' => $collapsible, 'id' => 'checkout-contact', 'title' => 'Contact'])
                    <fieldset class="checkout-shipping {{ $collapsible ? 'mb-0' : '' }}">
                        <legend class="{{ $collapsible ? 'visually-hidden' : '' }}">Contact</legend>
                        @guest('web')<p class="text-end mb-3"><a href="{{ route('login') }}">Sign in</a></p>@endguest
                        @auth('web')
                            <p class="mb-0">{{ auth('web')->user()->full_name }}<br>{{ auth('web')->user()->email }}</p>
                            <input type="hidden" name="email" value="{{ auth('web')->user()->email }}">
                        @else
                            @input(['bag' => 'default', 'id' => 'delivery-email', 'name' => 'email', 'type' => 'email', 'label' => 'Email', 'autocomplete' => 'email', 'value' => $recipient['email'] ?? session('shop_newsletter_email'), 'limit' => 254])
                        @endauth
                    </fieldset>
                    @endcomponent
                @endif
                @component('shop.storefront.checkout.section', ['collapsible' => $collapsible, 'id' => 'checkout-delivery', 'title' => 'Delivery address'])
                <fieldset class="checkout-shipping {{ $collapsible ? 'mb-0' : '' }}">
                    <legend class="{{ $collapsible ? 'visually-hidden' : '' }}">{{ $kind === 'digital' ? 'Billing details' : 'Delivery address' }}</legend>
                    <div class="row g-3">
                        @if($kind === 'digital')
                        <div class="col-12 col-sm-6">@input(['bag' => 'default', 'id' => 'delivery-email', 'name' => 'email', 'type' => 'email', 'label' => 'Email', 'autocomplete' => 'email', 'value' => optional(auth('web')->user())->email ?? session('shop_newsletter_email'), 'readonly' => $kind === 'digital', 'limit' => 254])</div>
                        @endif
                        <div class="col-12 col-sm-6">@input(['bag' => 'default', 'id' => 'delivery-name', 'name' => 'name', 'label' => 'Full name', 'autocomplete' => 'shipping name', 'value' => $recipient['name'] ?? optional(auth('web')->user())->full_name, 'limit' => 100])</div>
                        <div class="col-12">@input(['bag' => 'default', 'id' => 'delivery-address', 'name' => 'address1', 'label' => 'Street address', 'autocomplete' => 'shipping address-line1', 'value' => $recipient['address1'] ?? '', 'limit' => 150])</div>
                        <div class="col-12">@input(['bag' => 'default', 'id' => 'delivery-address2', 'name' => 'address2', 'label' => 'Apartment, suite, etc. (optional)', 'autocomplete' => 'shipping address-line2', 'value' => $recipient['address2'] ?? '', 'required' => false, 'limit' => 150])</div>
                        <div class="col-12 col-sm-6">@select(['bag' => 'default', 'id' => 'delivery-country', 'name' => 'country_code', 'label' => 'Country', 'value' => $recipient['country_code'] ?? '', 'choices' => ['' => 'Choose country'] + collect($countries)->pluck('name', 'code')->all()])</div>
                        <div class="col-12 col-sm-6" data-state-field>
                            @if($kind === 'digital')
                                @input(['bag' => 'default', 'id' => 'delivery-state', 'name' => 'state_code', 'label' => 'State / province', 'autocomplete' => 'billing address-level1', 'required' => false, 'limit' => 32])
                            @else
                                @select(['bag' => 'default', 'id' => 'delivery-state', 'name' => 'state_code', 'label' => 'State / province', 'value' => $recipient['state_code'] ?? '', 'choices' => ['' => 'Choose state / province'] + collect(collect($countries)->firstWhere('code', $recipient['country_code'] ?? '')['states'] ?? [])->pluck('name', 'code')->all()])
                            @endif
                        </div>
                        <div class="col-12 col-sm-6">@input(['bag' => 'default', 'id' => 'delivery-city', 'name' => 'city', 'label' => 'City', 'autocomplete' => 'shipping address-level2', 'value' => $recipient['city'] ?? '', 'limit' => 100])</div>
                        <div class="col-12 col-sm-6">@input(['bag' => 'default', 'id' => 'delivery-zip', 'name' => 'zip', 'label' => 'ZIP / postal code', 'autocomplete' => 'shipping postal-code', 'value' => $recipient['zip'] ?? '', 'required' => false, 'limit' => 20])</div>
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
                @endcomponent
            </form>
            <form data-checkout-payment data-merchandise-payment data-wallet-controller="{{ mix('js/components/express-checkout.js') }}">
                @csrf
                @component('shop.storefront.checkout.section', ['collapsible' => $collapsible, 'id' => 'checkout-payment-method', 'title' => 'Payment method'])
                @if($mode !== 'demo' && !$walletOnly)
                    @if($kind === 'digital') @include('components.billing.checkout.wallets') @endif
                    <div class="checkout-card-and-fields">
                        @include('components.billing.payment-card-preview')
                        @include('components.billing.payment-card-fields', ['prefix' => 'merchandise', 'cardholder' => optional(auth('web')->user())->full_name])
                    </div>
                    <p class="membership-card-status" data-card-status role="status">Loading secure card fields…</p>
                @elseif($collapsible)
                    <p class="mb-0">No payment is required for this test order.</p>
                @endif
                @endcomponent
                <p class="membership-card-errors" data-checkout-error role="alert" tabindex="-1"></p>
                <a href="#" data-checkout-order-link hidden>Check order status</a>
                <p class="membership-terms" @if($walletOnly) hidden @endif>By placing your order, you agree to the <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">Terms of Service</a>.</p>
                <div class="checkout-actions" @if($walletOnly) hidden @endif>
                    <button type="submit" class="btn btn-primary" disabled>@icon('lock') <span data-pay-label>{{ $mode === 'demo' ? 'Complete test order' : 'Pay securely' }}</span></button>
                    <a href="{{ $cancelUrl }}" class="checkout-cancel" data-checkout-cancel>Cancel and return</a>
                </div>
                <p class="membership-stripe-credit" @if($walletOnly) hidden @endif>{{ $mode === 'demo' ? 'Offline demonstration' : 'Payments securely processed by Stripe' }}</p>
            </form>
            @if($collapsible)</div>@endif
        </div>
