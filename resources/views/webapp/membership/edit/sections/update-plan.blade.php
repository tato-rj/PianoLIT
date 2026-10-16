@php
    $canChangePlan = in_array($source->status, ['active', 'trialing'], true) && !$overview['ended'] && !$source->isCanceled() && !$source->membership_ends_at && !$source->isPaused();
    $monthlyPlan = $plans->firstWhere('name', 'monthly');
@endphp
<div class="tab-pane fade" id="update-plan-tab" role="tabpanel" aria-labelledby="membership-tabs-update-plan-tab" tabindex="0">
    <div class="membership-plans mb-5">
        @foreach($plans->sortBy(function ($plan) { return $plan->name === 'yearly' ? 1 : 0; }) as $plan)
            @php($current = $source->plan === $plan->name)
            @component('components.membership-plan-card', ['plan' => $plan, 'monthlyPlan' => $monthlyPlan, 'headingId' => 'change-plan-'.$plan->name, 'current' => $current, 'selected' => false, 'accessNote' => 'Full Premium access on both plans'])
{{--                 @slot('context')
                <p class="text-center text-muted small mb-3">{{ $current ? 'Your current plan' : ($source->pending_plan === $plan->name ? 'Scheduled for your next renewal' : 'Prices in USD') }}</p>
                @endslot --}}
                @slot('action')
                <button type="button" class="btn btn-{{ $plan->name === 'yearly' ? 'primary' : 'secondary' }}" name="plan" data-name="{{ $plan->name }}" data-confirmation="{{ $current ? 'Your current plan will stay in place. Any scheduled plan change will be removed.' : ucfirst($plan->name).' billing will start at your next renewal'.($source->renews_at ? ' on '.$source->renews_at->toFormattedDateString() : '').'.' }}" aria-pressed="false" @if(!$canChangePlan || ($current && !$source->pending_plan)) disabled @endif>
                    @if($current)
                    @icon('badge-check'){{($source->pending_plan ? 'Keep this plan' : 'Current plan')}}
                    @else
                    Choose {{ ucfirst($plan->name) }}
                    @endif
                </button>
                @endslot
            @endcomponent
        @endforeach
    </div>
    <div class="text-center" id="update-plan-action">
        @if(!$canChangePlan)
        <p class="text-muted">Your membership must be active and renewing before you can change plans. Resume billing or resolve any payment issues first.</p>
        <a class="btn btn-secondary" href="{{ $overview['ended'] ? route('webapp.membership.pricing') : '#update-card-tab' }}" @if(!$overview['ended']) data-membership-tab @endif>{{ $overview['ended'] ? 'Explore plans' : 'Manage billing' }}</a>
        @else
        <p class="text-muted small">Changes take effect at your next renewal. Selecting a plan does not charge your card.</p>
        <form method="POST" action="{{ route('webapp.membership.update.plan') }}" style="display: none;" disable-on-submit>
            @csrf
            <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <p class="text-muted" data-plan-confirmation role="status"></p>
            <input type="hidden" name="plan">
            <button type="submit" id="update-plan-button" class="btn btn-primary">Confirm plan change</button>
        </form>
        @endif
    </div>
    <p class="text-muted small text-center mt-3 mb-0">Cards show current plan prices in USD. <a href="#invoices-tab" data-membership-tab>View your invoices</a> for your current charges and discounts.</p>
</div>
