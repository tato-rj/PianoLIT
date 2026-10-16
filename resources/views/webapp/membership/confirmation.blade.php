<div class="modal fade membership-confirmation" id="membership-confirmation-modal" tabindex="-1" aria-labelledby="membership-confirmation-title" aria-describedby="membership-confirmation-description" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <span class="membership-confirmation-icon" aria-hidden="true">@icon('circle-check', ['mr' => 0])</span>
                <h2 id="membership-confirmation-title">You’re all set!</h2>
                <p class="membership-confirmation-intro" id="membership-confirmation-description">{{ $membershipConfirmation['trial'] ? 'Your PianoLIT Premium free trial has started.' : 'Your PianoLIT Premium membership is active.' }}<br>Enjoy full access to scores, videos, and recordings.</p>

                <dl class="membership-confirmation-details">
                    <div>
                        <dt>@icon('crown', ['mr' => 0])<span>Membership</span></dt>
                        <dd>{{ $membershipConfirmation['plan'] }}</dd>
                    </div>
                    <div>
                        <dt>@icon('circle-check', ['mr' => 0])<span>Status</span></dt>
                        <dd><span class="badge rounded-pill alert-{{ $membershipConfirmation['tone'] }}">{{ $membershipConfirmation['status'] }}</span></dd>
                    </div>
                    <div>
                        <dt>@icon('calendar-days', ['mr' => 0])<span>Purchase date</span></dt>
                        <dd>@if($membershipConfirmation['purchased_at'])<time datetime="{{ $membershipConfirmation['purchased_at']->toDateString() }}">{{ $membershipConfirmation['purchased_at']->toFormattedDateString() }}</time>@else Not available @endif</dd>
                    </div>
                    <div>
                        <dt>@icon('credit-card', ['mr' => 0])<span>Next charge</span></dt>
                        <dd>@if($membershipConfirmation['next_charge_at'])<time datetime="{{ $membershipConfirmation['next_charge_at']->toDateString() }}">{{ $membershipConfirmation['next_charge_at']->toFormattedDateString() }}</time>@else Not scheduled @endif</dd>
                    </div>
                    @if($membershipConfirmation['trial'])
                    <div>
                        <dt>@icon('clock', ['mr' => 0])<span>Trial ends</span></dt>
                        <dd>@if($membershipConfirmation['trial_ends_at'])<time datetime="{{ $membershipConfirmation['trial_ends_at']->toDateString() }}">{{ $membershipConfirmation['trial_ends_at']->toFormattedDateString() }}</time>@else Not available @endif</dd>
                    </div>
                    @endif
                </dl>

                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">Start exploring @icon('arrow-right', ['mr' => 0])</button>
                <p class="membership-confirmation-footer">
                    @if($membershipConfirmation['email_queued'])
                        Confirmation sent to your email
                    @else
                        Your membership details are in <a href="{{ route('webapp.membership.edit') }}">My Membership</a>
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    var element = document.getElementById('membership-confirmation-modal');
    // Keep the dialog outside page containers, matching Bootstrap's normal modal behavior.
    document.body.appendChild(element);
    var modal = new bootstrap.Modal(element);
    element.addEventListener('hidden.bs.modal', function () {
        modal.dispose();
        element.remove();
        var heading = document.querySelector('main h1, main h2');
        if (heading) {
            heading.setAttribute('tabindex', '-1');
            heading.focus({preventScroll: true});
        }
    }, {once: true});
    modal.show();
});
</script>
@endpush
