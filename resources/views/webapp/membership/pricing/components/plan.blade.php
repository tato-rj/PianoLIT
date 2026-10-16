@php
    $isYearly = $plan->name === 'yearly';
    $trial = !$hasPreviousMembership ? max(0, (int) $plan->trial_period_days) : 0;
    $savings = $isYearly && $monthlyPlan && $monthlyPlan->price > 0 ? max(0, (int) round(100 - $plan->price / ($monthlyPlan->price * 12) * 100)) : 0;
@endphp
<article class="membership-plan {{ $isYearly ? 'membership-plan--featured' : '' }}" aria-labelledby="plan-{{ $plan->name }}">
    <div class="membership-plan-top">
        <h2 id="plan-{{ $plan->name }}">{{ ucfirst($plan->name) }}</h2>
        @if($savings)
        <span class="membership-badge">Save {{ $savings }}%</span>
        @endif
    </div>
    <p class="membership-plan-intro">{{ $isYearly ? 'A year of inspiration. Our best value.' : 'A little more flexibility, month to month.' }}</p>
    <div class="membership-price">
        <strong>${{ number_format($plan->price / ($isYearly ? 1200 : 100), 2) }}</strong><span>/ month</span>
    </div>
    <p class="membership-billing-line">${{ number_format($plan->price / 100, 2) }} billed {{ $isYearly ? 'yearly' : 'monthly' }}{{ $trial ? ', after your free trial' : '' }}.</p>
    <a href="{{ route('webapp.membership.checkout', $plan->name) }}" class="btn {{ $isYearly ? 'btn-primary' : 'btn-secondary' }} w-100">
        {{ $trial ? 'Start '.$trial.'-day free trial' : 'Choose '.ucfirst($plan->name) }} @icon('arrow-right', ['mr' => 0])
    </a>
    <p class="membership-plan-footnote">{{ $trial ? 'For new members. Cancel before your trial ends.' : 'Full access as soon as your payment is confirmed.' }}</p>
</article>
