<form action="{{ route('webapp.membership.purchase', $plan) }}" method="POST" id="payment-form" class="mb-4 payment-forms"
    data-membership-payment data-purpose="purchase" data-key="{{ $stripeKey }}" data-operation="{{ $operationId }}"
    data-prepare="{{ route('webapp.membership.prepare', $plan) }}" data-complete="{{ route('webapp.membership.complete') }}">
    @csrf
    <div class="mb-3">
        <div class="form-group mb-3">
            <div id="card-element" class="form-control"></div>
            <div id="card-errors" role="alert" aria-live="polite" class="invalid-feedback d-block"></div>
        </div>
        <div class="form-group mb-3">
            <label class="visually-hidden" for="membership-coupon">Coupon code</label>
            <input type="text" id="membership-coupon" name="coupon" placeholder="Have a coupon code?" class="form-control" maxlength="80"
                value="{{ $activeCoupon ?? '' }}" data-coupon-url="{{ route('webapp.membership.coupon', $plan) }}" autocomplete="off">
            <div data-coupon-feedback class="d-block" role="status" aria-live="polite"></div>
        </div>
        <div><small>By subscribing I agree to the <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer" class="link-blue">Terms of Service</a>.</small></div>
    </div>
    <button type="submit" id="card-button" class="btn d-block w-100 btn-default">@icon('lock'){{ $trialDays ? 'Start my free trial' : 'Subscribe for $'.$plan->formattedPrice() }}</button>
</form>
<div class="text-muted mb-1"><small>
    @if($trialDays)
        Your {{ $trialDays }}-day free trial starts when checkout is complete. Unless you cancel before it ends, your first payment is ${{ $plan->formattedPrice() }}.
    @else
        Payment of ${{ $plan->formattedPrice() }} is due when your subscription starts. Returning members do not receive another introductory trial.
    @endif
    Your subscription then renews every {{ $plan->interval }} until you cancel. A valid coupon changes the discounted payments according to its terms.
</small></div>
<div class="text-muted"><small>All prices are listed in US Dollars.</small></div>
