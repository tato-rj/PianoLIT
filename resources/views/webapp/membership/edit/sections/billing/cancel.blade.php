@php
    $ending = !$overview['ended'] && ($source->isCanceled() || $source->membership_ends_at);
    $renewalScheduled = !$overview['ended'] && !$ending && !$source->isPaused()
        && in_array($source->status, ['active', 'trialing'], true) && $source->renews_at && $source->renews_at->isFuture();
@endphp
<section class="bg-light rounded-sm p-4 mt-5" aria-labelledby="membership-renewal-heading">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <span class="badge alert-blue rounded p-3" aria-hidden="true">@icon('calendar', ['size' => 'lg', 'mr' => 0])</span>
            <div>
                <h2 class="h5 mb-1" id="membership-renewal-heading">{{ $overview['ended'] ? 'Membership ended' : ($ending ? 'Your membership is ending' : 'Membership renewal') }}</h2>
                <p class="text-muted small mb-0">Your plan and automatic renewal settings.</p>
            </div>
        </div>
        <span class="badge rounded-pill alert-{{ $overview['tone'] }}">{{ $ending ? 'Renewal off' : ($renewalScheduled ? 'Auto-renew on' : $overview['status']) }}</span>
    </div>

    <dl class="membership-overview-details bg-white rounded-sm p-3 mb-0">
        <div>
            <dt>{{ $overview['ended'] ? 'Previous plan' : 'Your plan' }}</dt>
            <dd class="font-weight-bold">{{ $source->plan_name }} Premium</dd>
        </div>
        <div>
            <dt>{{ $overview['dateLabel'] }}</dt>
            <dd class="font-weight-bold">{{ $overview['date'] ? $overview['date']->toFormattedDateString() : ($overview['dateText'] ?? 'Not available') }}</dd>
        </div>
    </dl>

    <div class="row align-items-center g-3 mt-1">
        <div class="col-md">
            <p class="text-muted small mb-0">
                @if($overview['ended'])
                    Your membership has ended. You can still view your past invoices.
                @elseif($ending)
                    Your membership will not renew.@if($source->membership_ends_at) You keep access until <strong>{{ $source->membership_ends_at->toFormattedDateString() }}</strong>.@endif
                @elseif($source->isPaused())
                    Payments are paused. Resume billing to restart payments, or cancel your membership.
                @elseif($renewalScheduled)
                    Cancel anytime. Your {{ $source->isOnTrial() ? 'trial' : 'Premium' }} access continues until {{ $source->isOnTrial() ? 'your trial ends' : 'the end of your billing period' }}.
                @else
                    Canceling stops future membership renewals. Any outstanding invoices remain available in Invoices.
                @endif
            </p>
        </div>
        <div class="col-md-auto">
            @if($overview['ended'])
                <a class="btn btn-secondary w-100" href="#invoices-tab" data-membership-tab>View invoices @icon('arrow-right', ['mr' => 0, 'ml' => 1])</a>
            @elseif($ending)
                <form method="POST" action="{{ route('webapp.membership.resume') }}" disable-on-submit>
                    @csrf
                    <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <button type="submit" class="btn btn-secondary w-100">Keep my membership</button>
                </form>
            @else
                <div class="d-flex flex-wrap gap-2">
                    @if($source->isPaused())
                    <form method="POST" action="{{ route('webapp.membership.update.billing-status') }}" disable-on-submit>
                        @csrf
                        <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                        <input type="hidden" name="paused" value="0">
                        <button type="submit" class="btn btn-secondary">Resume billing</button>
                    </form>
                    @endif
                    <button type="button" class="btn btn-outline-danger flex-grow-1" data-bs-toggle="modal" data-bs-target="#membership-cancel-confirmation">Cancel membership</button>
                </div>
            @endif
        </div>
    </div>
</section>

@if(!$overview['ended'] && !$ending)
    @push('page-modals')
        @include('webapp.membership.edit.sections.billing.cancel-modal')
    @endpush
@endif
