@php
    $isYearly = $plan->name === 'yearly';
    $trial = $trial ?? 0;
    $savings = $isYearly && isset($monthlyPlan) && $monthlyPlan->price > 0 ? max(0, (int) round(100 - $plan->price / ($monthlyPlan->price * 12) * 100)) : 0;
@endphp
<article class="membership-plan {{ $isYearly ? 'membership-plan--featured' : '' }} {{ ($current ?? false) ? 'shadow-none border border-light' : '' }}" aria-labelledby="{{ $headingId }}" @if(isset($selected)) data-plan-card data-selected="{{ $selected ? 'true' : 'false' }}" @endif @if($preview ?? false) data-ds-preview @endif>
    <div class="membership-plan-panel">
        <div class="membership-plan-top">
            <h2 id="{{ $headingId }}">{{ ucfirst($plan->name) }}</h2>
            @if($savings)
            <span class="membership-plan-saving text-red">Save {{ $savings }}%</span>
            @endif
        </div>
        <div>
            <div class="membership-price">
                <strong>${{ number_format($plan->price / ($isYearly ? 1200 : 100), 2) }}</strong><span>/ month</span>
            </div>
            <p class="membership-billing-line">${{ number_format($plan->price / 100, 2) }} billed {{ $isYearly ? 'yearly' : 'monthly' }}{{ $trial ? ', after your free trial' : '' }}.</p>
        </div>
    </div>
    <ul class="membership-plan-benefits list-style-none ps-3">
        <li><span class="badge bg-elementary mr-2">@icon('headphones', ['mr' => 0])</span>Full recordings and videos</li>
        <li><span class="badge bg-beginner mr-2">@icon('book-open', ['mr' => 0])</span>Complete public-domain scores</li>
        <li><span class="badge bg-intermediate mr-2">@icon('heart', ['mr' => 0])</span>Your own saved score markings</li>
        <li><span class="badge bg-advanced mr-2">@icon('badge-check', ['mr' => 0])</span>{{ $accessNote ?? ($trial ? $trial.'-day trial for new members' : 'Full access after payment confirmation') }}</li>
    </ul>
    {{ $context ?? '' }}
    {{ $action }}
</article>
