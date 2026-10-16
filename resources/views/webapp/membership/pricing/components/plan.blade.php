@php($trial = !$hasPreviousMembership ? max(0, (int) $plan->trial_period_days) : 0)
@component('components.membership-plan-card', ['plan' => $plan, 'monthlyPlan' => $monthlyPlan, 'trial' => $trial, 'headingId' => 'plan-'.$plan->name])
    @slot('action')
    <a href="{{ route('webapp.membership.checkout', $plan->name) }}" class="btn btn-{{$plan->name === 'yearly' ? 'primary' : 'secondary';}}" data-checkout-open>
        {{ $trial ? 'Start '.$trial.'-day free trial' : 'Choose '.ucfirst($plan->name) }}@icon('arrow-right', ['mr' => 0, 'ml' => '2'])
    </a>
    @endslot
@endcomponent
