<form action="{{ route('webapp.membership.purchase', $plan) }}" method="POST" id="payment-form" class="membership-payment-form"
    data-checkout-payment data-membership-payment data-purpose="purchase" data-key="{{ $stripeKey }}" data-operation="{{ $operationId }}" data-split-card
    data-prepare="{{ route('webapp.membership.prepare', $plan) }}" data-complete="{{ route('webapp.membership.complete') }}">
    @csrf
    <div class="checkout-card-and-fields">
        @include('components.payment-card-preview')
    <fieldset class="membership-card-fields">
        <legend>@icon('credit-card') Credit or debit card</legend>
        <div class="membership-field">
            <label for="card-number">Card number</label>
            <div id="card-number" class="form-control membership-stripe-field" data-stripe-field="cardNumber"></div>
        </div>
        <div class="membership-field">
            <label for="membership-card-name">Name on card</label>
            <input id="membership-card-name" type="text" class="form-control" name="cardholder" autocomplete="cc-name" maxlength="255" value="{{ auth('web')->user()->full_name }}" required>
        </div>
        <div class="membership-card-row">
            <div class="membership-field">
                <label for="card-expiry">Expiration date</label>
                <div id="card-expiry" class="form-control membership-stripe-field" data-stripe-field="cardExpiry"></div>
            </div>
            <div class="membership-field">
                <label for="card-cvc">Security code</label>
                <div id="card-cvc" class="form-control membership-stripe-field" data-stripe-field="cardCvc"></div>
            </div>
        </div>
        <div class="membership-field">
            <label for="membership-postal-code">Billing ZIP / postal code <span class="text-muted">(optional)</span></label>
            <input id="membership-postal-code" class="form-control" name="postal_code" type="text" autocomplete="billing postal-code" maxlength="20" placeholder="ZIP / postal code">
        </div>
    </fieldset>
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
