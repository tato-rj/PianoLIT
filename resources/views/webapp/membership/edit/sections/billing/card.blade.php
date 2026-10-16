<section aria-labelledby="membership-payment-heading" data-payment-methods>
    <h2 class="h4 mb-2" id="membership-payment-heading">Payment method</h2>
    <p class="text-muted mb-4">{{ $overview['ended'] ? 'Your last membership payment method. Past payments are available in Invoices.' : ($source->hasCard() ? 'The checked card is used for your membership payments.' : 'Add a payment method for future membership payments.') }}</p>
    <div class="membership-saved-cards">
        @if($source->hasCard())
            @include('components.payment-card-preview', ['saved' => ['brand' => $source->card_brand, 'last4' => $source->card_last_four]])
        @else
            <p class="text-muted mb-0">No card on file.</p>
        @endif
        @unless($overview['ended'])
            @include('components.payment-card-preview', ['add' => true, 'controlsId' => 'membership-card-editor'])
        @endunless
    </div>
    @unless($overview['ended'])
    <div id="membership-card-editor" class="membership-card-editor bg-light rounded p-4 mt-4" data-payment-card-editor hidden>
        <h3 class="h5 mb-2">Add a new card</h3>
        <p class="text-muted small mb-4">Once verified, this card will replace your current payment method for future membership payments.</p>
        <form action="{{ route('webapp.membership.update.card') }}" method="POST" id="update-card-form" data-membership-payment data-purpose="card" data-deferred-card data-split-card
            data-key="{{ $stripeKey }}" data-operation="{{ $cardOperation }}" data-prepare="{{ route('webapp.membership.prepare.card') }}">
            @csrf
            <div class="payment-card-form-layout">
                @include('components.payment-card-preview')
                @include('components.payment-card-fields', ['prefix' => 'billing', 'cardholder' => auth('web')->user()->full_name])
            </div>
            <div data-card-status class="membership-card-status mt-3" role="status" aria-live="polite">Loading secure card fields…</div>
            <div id="card-errors" role="alert" aria-live="polite" class="membership-card-errors" tabindex="-1"></div>
            <div class="d-flex align-items-center flex-wrap gap-3 mt-4">
                <button type="submit" class="btn btn-primary" disabled>@icon('lock')<span data-submit-label>Save card</span></button>
                <button type="button" class="btn btn-link" data-payment-card-cancel>Cancel</button>
            </div>
            <p class="membership-stripe-credit text-start mb-0">Payments securely processed by <strong>stripe</strong></p>
        </form>
    </div>
    <noscript><p class="text-danger mt-3">Please enable JavaScript to add a payment method.</p></noscript>
    @endunless
</section>
