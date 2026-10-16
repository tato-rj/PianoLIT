<div class="checkout-order-item">
    <span class="membership-eyebrow">{{ ucfirst($plan->name) }} membership</span>
    <h3>PianoLIT Premium</h3>
    <p>Full recordings, videos and public-domain scores, with your own saved score markings.</p>
    <a href="{{ route('webapp.membership.pricing') }}" data-checkout-cancel>Change plan</a>
</div>
<div class="membership-order-lines">
    <div><span>{{ $plan->name === 'yearly' ? 'Yearly' : 'Monthly' }} membership</span><strong data-membership-total data-base-price="{{ $plan->price }}">${{ number_format($plan->price / 100, 2) }}</strong></div>
    @if($trialDays)
    <div><span>Free trial</span><strong>{{ $trialDays }} days</strong></div>
    @endif
    <div class="membership-due"><span>Due today <small>(USD)</small></span><strong @unless($trialDays) data-membership-due @endunless>${{ $trialDays ? '0.00' : number_format($plan->price / 100, 2) }}</strong></div>
</div>
<p class="membership-summary-note" data-membership-renewal>
    @if($trialDays)
        Then ${{ number_format($plan->price / 100, 2) }} every {{ $plan->interval }}. Cancel before your {{ $trialDays }}-day trial ends to avoid the first payment.
    @else
        ${{ number_format($plan->price / 100, 2) }} every {{ $plan->interval }}, starting today. Cancel anytime to stop future renewals.
    @endif
</p>
<p class="checkout-summary-help">Manage your plan anytime in My Membership.<br>Need help? <a href="mailto:contact@pianolit.com">Contact us</a></p>
