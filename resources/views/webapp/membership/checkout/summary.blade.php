@php($otherPlan = $plan->other())
<aside class="membership-summary" aria-labelledby="membership-summary-heading">
    <div class="membership-plan-top">
        <span class="membership-eyebrow">Your membership</span>
        @if($otherPlan)
        <a href="{{ route('webapp.membership.checkout', $otherPlan) }}" aria-label="Switch to {{ $otherPlan->name }} billing">Change plan</a>
        @endif
    </div>
    <h2 id="membership-summary-heading">PianoLIT Premium</h2>
    <p>{{ ucfirst($plan->name) }} membership · Full Premium access</p>
    <div class="membership-order-lines">
        <div><span>{{ $plan->name === 'yearly' ? 'Yearly' : 'Monthly' }} price</span><strong data-membership-total data-base-price="{{ $plan->price }}">${{ number_format($plan->price / 100, 2) }}</strong></div>
        @if($trialDays)
        <div><span>Free trial</span><strong>{{ $trialDays }} days</strong></div>
        @endif
        <div class="membership-due"><span>Due today</span><strong @unless($trialDays) data-membership-due @endunless>${{ $trialDays ? '0.00' : number_format($plan->price / 100, 2) }}</strong></div>
    </div>
    <p class="membership-summary-note" data-membership-renewal data-trial-days="{{ $trialDays }}" data-interval="{{ $plan->interval }}">
        @if($trialDays)
            Then ${{ number_format($plan->price / 100, 2) }} every {{ $plan->interval }}. Cancel before your {{ $trialDays }}-day trial ends to avoid the first payment.
        @else
            ${{ number_format($plan->price / 100, 2) }} every {{ $plan->interval }}, starting today. Cancel anytime to stop future renewals.
        @endif
    </p>
    <ul class="membership-included" aria-label="Included with Premium">
        <li>@icon('check', ['mr' => 0]) Full recordings and videos</li>
        <li>@icon('check', ['mr' => 0]) Complete public-domain scores</li>
        <li>@icon('check', ['mr' => 0]) Your own saved score markings</li>
        <li>@icon('check', ['mr' => 0]) Inspiration for your next piece</li>
    </ul>
    <p class="membership-summary-note mb-0">Manage your plan and billing anytime in My Membership. All prices in USD.</p>
</aside>
