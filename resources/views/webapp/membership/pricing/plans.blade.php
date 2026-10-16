@php
    $hasPreviousMembership = auth('web')->check() && auth('web')->user()->membership()->exists();
    $monthlyPlan = $plans->firstWhere('name', 'monthly');
@endphp
<section aria-label="Premium membership plans">
    <div class="membership-plans">
        @foreach($plans->sortBy(function ($plan) { return $plan->name === 'yearly' ? 1 : 0; }) as $plan)
            @include('webapp.membership.pricing.components.plan')
        @endforeach
    </div>
    <div class="membership-reassurance">
        <span>@icon('check', ['mr' => 0]) Full Premium access on both plans</span>
        <span>@icon('lock', ['mr' => 0]) Secure payments with Stripe</span>
        <span>Prices in USD · Cancel anytime</span>
    </div>
</section>
