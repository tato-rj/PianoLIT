@component('components.dialogs.modal', ['id' => 'remove-card', 'header' => 'Remove saved card', 'centered' => true])
    @slot('body')
    <form method="POST" action="{{ route('shop.payment-method.remove') }}">
        @csrf
        @method('DELETE')
        <p>Remove {{ ucfirst(auth()->user()->customer->card_brand) }} ending in {{ auth()->user()->customer->card_last_four }} from your saved payment methods?</p>
        <p class="text-muted small">You can enter a new card at checkout.</p>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Remove card</button>
        </div>
    </form>
    @endslot
@endcomponent
