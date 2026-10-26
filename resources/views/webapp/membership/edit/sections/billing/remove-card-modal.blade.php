@component('components.dialogs.modal', ['id' => 'membership-remove-card-confirmation', 'header' => 'Remove saved card?', 'centered' => true, 'descriptionId' => 'membership-remove-card-description', 'options' => ['body' => ['padding' => 3]]])
    @slot('body')
        <div class="bg-light rounded-sm p-3 d-flex align-items-center gap-3 mb-3">
            @icon('credit-card', ['mr' => 0, 'classes' => 'text-muted icon-size-lg'])
            <span class="font-weight-bold">{{ ucfirst($savedCard['brand']) }} ending in {{ $savedCard['last4'] }}</span>
        </div>
        <div id="membership-remove-card-description">
            <p class="text-muted {{ $overview['ended'] ? 'mb-0' : 'mb-3' }}">This card will be removed from your saved membership payment methods. Your membership records and invoices will stay available. You can add a card again later.</p>
            @unless($overview['ended'])
                <p class="text-muted mb-0">Removing this card does not cancel your membership. Add another card before your next payment to avoid a failed payment and an interruption to your access.</p>
            @endunless
        </div>
    @endslot
    @slot('footer')
        <form method="POST" action="{{ route('webapp.membership.remove.card') }}" class="d-grid d-sm-flex justify-content-sm-end gap-2 w-100" disable-on-submit>
            @csrf
            <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="saved_card_reference" value="{{ $savedCard['reference'] }}">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep card</button>
            <button type="submit" class="btn btn-danger">Remove card</button>
        </form>
    @endslot
@endcomponent
