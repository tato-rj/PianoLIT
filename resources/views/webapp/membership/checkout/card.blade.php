<form action="{{ route('webapp.membership.purchase', $plan) }}" method="POST" id="payment-form" class="membership-payment-form"
    @if($savedCard) data-saved-card-reference="{{ $savedCard['reference'] }}" @endif data-checkout-payment data-membership-payment data-purpose="purchase" data-key="{{ $stripeKey }}" data-operation="{{ $operationId }}" data-split-card
    data-prepare="{{ route('webapp.membership.prepare', $plan) }}" data-complete="{{ route('webapp.membership.complete') }}"
    data-wallet-controller="{{ mix('js/components/express-checkout.js') }}" data-wallet-amount="{{ $plan->price }}"
    data-wallet-interval="{{ $plan->interval }}" data-wallet-trial="{{ $trialDays }}" data-wallet-manage="{{ route('webapp.membership.edit') }}"
    data-wallet-return="{{ route('webapp.membership.checkout', $plan) }}" data-wallet-resume="{{ $walletResume ?? '' }}">
    @csrf
    @include('components.billing.checkout.wallets')
    <div class="checkout-card-and-fields">
        @if($savedCard)
        <div class="d-grid gap-3">
            @include('components.billing.payment-card-preview', ['saved' => $savedCard, 'selectable' => true])
            @include('components.billing.payment-card-preview', ['add' => true, 'controlsId' => 'checkout-new-card-fields'])
            <div data-checkout-new-preview hidden>
                @include('components.billing.payment-card-preview', ['saved' => null, 'add' => false])
            </div>
        </div>
        <div>
            <div data-checkout-saved-details>
                <h2 class="h5 mb-3">Pay with your saved card</h2>
                <p class="text-muted">Use the card on file for your new membership, or add a different card.</p>
                <p class="text-muted small mb-0">Your card will be charged when you confirm your membership.</p>
            </div>
            <fieldset class="border-0 p-0 m-0" id="checkout-new-card-fields" data-checkout-new-fields hidden disabled>
                @include('components.billing.payment-card-fields', ['prefix' => 'checkout', 'cardholder' => auth('web')->user()->full_name])
            </fieldset>
        </div>
        @else
            @include('components.billing.payment-card-preview')
            @include('components.billing.payment-card-fields', ['prefix' => 'checkout', 'cardholder' => auth('web')->user()->full_name])
        @endif
    </div>
    <div data-card-status class="membership-card-status" role="status" aria-live="polite">Loading secure card fields…</div>
    <div id="card-errors" role="alert" aria-live="polite" class="membership-card-errors" tabindex="-1"></div>
    <details class="membership-coupon" @if($activeCoupon) open @endif>
        <summary>Have a coupon code? @icon('plus', ['mr' => 0])</summary>
        <div class="membership-coupon-controls">
            <div class="flex-grow-1">
                <label class="visually-hidden" for="membership-coupon">Coupon code</label>
                <input type="text" id="membership-coupon" name="coupon" placeholder="Enter code" class="form-control" maxlength="80"
                    value="{{ $activeCoupon ?? '' }}" data-coupon-url="{{ route('webapp.membership.coupon', $plan) }}" autocomplete="off" aria-describedby="coupon-feedback">
            </div>
            <button type="button" class="btn btn-secondary" data-apply-coupon>Apply</button>
        </div>
        <div id="coupon-feedback" data-coupon-feedback role="status" aria-live="polite"></div>
    </details>
    <p class="membership-terms" data-membership-consent data-base-price="{{ $plan->price }}" data-trial-days="{{ $trialDays }}" data-interval="{{ $plan->interval }}">
        <span data-membership-charge>
        @if($trialDays)
            Your {{ $trialDays }}-day free trial starts when checkout is complete. Then ${{ number_format($plan->price / 100, 2) }} is charged every {{ $plan->interval }} until you cancel.
        @else
            You authorize a payment of ${{ number_format($plan->price / 100, 2) }} today, then every {{ $plan->interval }} until you cancel. Returning members do not receive another introductory trial.
        @endif
        </span>
        By subscribing, you agree to the <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">Terms of Service</a>.
    </p>
    <div class="checkout-actions">
    <button type="submit" id="card-button" class="btn btn-primary" disabled>
        @icon('lock') <span data-submit-label data-base-price="{{ $plan->price }}" data-trial-days="{{ $trialDays }}">{{ $trialDays ? 'Start my '.$trialDays.'-day free trial' : 'Subscribe for $'.number_format($plan->price / 100, 2) }}</span>
    </button>
    <a href="{{ route('webapp.membership.pricing') }}" class="checkout-cancel" data-checkout-cancel>Cancel and return</a>
    </div>
    <p class="membership-stripe-credit">Payments securely processed by <strong>stripe</strong></p>
    <noscript><p class="text-danger">Please enable JavaScript to use the secure card form.</p></noscript>
</form>
