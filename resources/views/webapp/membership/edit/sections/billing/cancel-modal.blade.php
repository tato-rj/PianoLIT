@component('components.dialogs.modal', ['id' => 'membership-cancel-confirmation', 'header' => 'Cancel your membership?', 'centered' => true, 'descriptionId' => 'membership-cancel-description', 'options' => ['body' => ['padding' => 3]]])
    @slot('body')
        <p class="text-muted small mb-3">{{ $source->plan_name }} Premium</p>
        <div id="membership-cancel-description">
            @if($renewalScheduled)
                <p class="mb-3">Your membership will not renew. You’ll keep full access until your {{ $source->isOnTrial() ? 'trial' : 'current billing period' }} ends.</p>
                <div class="bg-light rounded-sm p-3">
                    <p class="small text-muted mb-1">{{ $source->isOnTrial() ? 'Trial access ends' : 'Premium access ends' }}</p>
                    <p class="font-weight-bold mb-0">{{ $source->renews_at->toFormattedDateString() }}</p>
                </div>
            @elseif($source->status === 'incomplete')
                <p class="mb-0">Your unpaid checkout will be canceled immediately.</p>
            @else
                <p class="mb-0">Canceling stops future membership renewals. Any outstanding invoices remain available in Invoices.</p>
            @endif
        </div>
    @endslot
    @slot('footer')
        <form method="POST" action="{{ route('webapp.membership.cancel') }}" id="cancel-membership-form" class="d-grid d-sm-flex justify-content-sm-end gap-2 w-100" disable-on-submit>
            @csrf
            <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep membership</button>
            <button type="submit" class="btn btn-danger">Confirm cancellation</button>
        </form>
    @endslot
@endcomponent
