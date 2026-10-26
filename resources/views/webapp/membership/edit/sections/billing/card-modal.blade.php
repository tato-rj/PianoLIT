@component('components.dialogs.modal', ['id' => 'membership-card-editor', 'header' => 'Add a new card', 'centered' => true, 'descriptionId' => 'membership-card-description', 'data' => ['payment-card-editor' => 'true'], 'options' => ['size' => 'lg', 'body' => ['padding' => 3]]])
    @slot('body')
        <p class="text-muted small mb-4" id="membership-card-description">Once verified, this card will replace your current payment method for future membership payments.</p>
        <form action="{{ route('webapp.membership.update.card') }}" method="POST" id="update-card-form" data-membership-payment data-purpose="card" data-deferred-card data-split-card
            data-key="{{ $stripeKey }}" data-operation="{{ $cardOperation }}" data-prepare="{{ route('webapp.membership.prepare.card') }}">
            @csrf
            <div class="payment-card-form-layout">
                @include('components.billing.payment-card-preview')
                @include('components.billing.payment-card-fields', ['prefix' => 'billing', 'cardholder' => auth('web')->user()->full_name])
            </div>
            <div data-card-status class="membership-card-status mt-3" role="status" aria-live="polite">Loading secure card fields…</div>
            <div id="card-errors" role="alert" aria-live="polite" class="membership-card-errors" tabindex="-1"></div>
            <div class="d-flex align-items-center flex-wrap gap-3 mt-4">
                <button type="submit" class="btn btn-primary" disabled>@icon('lock')<span data-submit-label>Save card</span></button>
                <button type="button" class="btn btn-link" data-bs-dismiss="modal" data-payment-card-cancel>Cancel</button>
            </div>
            <p class="membership-stripe-credit text-start mb-0">Payments securely processed by <strong>stripe</strong></p>
        </form>
    @endslot
@endcomponent
