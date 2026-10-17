<div class="tab-pane fade show active" id="overview-tab" role="tabpanel" aria-labelledby="membership-tabs-overview-tab" tabindex="0">
    <div class="membership-overview-grid">
        <section class="card border-0" aria-labelledby="membership-current-plan">
            <div class="card-body p-4 d-flex flex-column">
                <div class="mb-3">
                    <div>
                        <div class="mb-1">
                            <span class="badge rounded-pill alert-{{ $overview['tone'] }}">{{ $overview['status'] }}</span>
                        </div>
                        <h2 class="h4 mb-0" id="membership-current-plan">{{ $source->plan_name }} membership</h2>
                    </div>
                </div>
                <p class="text-muted mb-4">{{ $overview['note'] }}</p>
                <dl class="membership-overview-details mb-4">
                    <div>
                        <dt>@icon('calendar')Membership started</dt>
                        <dd>{{ $source->created_at->toFormattedDateString() }}</dd>
                    </div>
                    <div>
                        <dt>@icon('calendar'){{ $overview['dateLabel'] }}</dt>
                        <dd>{{ $overview['date'] ? $overview['date']->toFormattedDateString() : ($overview['dateText'] ?? 'Not available') }}</dd>
                    </div>
                </dl>
            </div>
        </section>
        <section class="card rounded-sm" aria-labelledby="membership-payment-method">
            <div class="card-body p-4 d-flex flex-column">
                <h2 class="h6 mb-4" id="membership-payment-method">Payment method</h2>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="badge alert-blue p-3 rounded">@icon('credit-card', ['size' => 'lg', 'mr' => 0])</span>
                    <div>
                        <p class="mb-0 font-weight-bold">
                            @if($source->hasCard())
                            {{ucfirst($source->card_brand)}} ending in <strong>{{$source->card_last_four}}</strong>
                            @else
                            No card on file
                            @endif
                        </p>
                        <span class="text-muted small">Payments processed by Stripe</span>
                    </div>
                </div>
                <p class="text-muted small mb-4">{{ $overview['ended'] ? 'Your past payments and receipts are available in Invoices.' : 'Manage your payment method and billing preferences in one place.' }}</p>
                <div class="border-top pt-4 mt-auto">
                    <a class="btn btn-secondary" href="{{ $overview['ended'] ? '#invoices-tab' : '#update-card-tab' }}" data-membership-tab>{{ $overview['ended'] ? 'View invoices' : 'Manage billing' }}</a>
                </div>
            </div>
        </section>
    </div>
</div>
