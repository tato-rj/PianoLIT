<section aria-label="Payment method" data-payment-methods>
    {{-- <h2 class="h4 mb-2" id="membership-payment-heading">Payment method</h2> --}}
    <p class="text-muted mb-4">{{ $overview['ended'] ? 'Your last membership payment method. Past payments are available in Invoices.' : ($source->hasCard() ? 'The checked card is used for your membership payments.' : 'Add a payment method for future membership payments.') }}</p>
    <div class="membership-saved-cards">
        @if($source->hasCard())
        <div>
            @include('components.payment-card-preview', ['saved' => ['brand' => $source->card_brand, 'last4' => $source->card_last_four]])
            <button type="button" class="btn btn-link text-danger mt-3" data-bs-toggle="modal" data-bs-target="#membership-remove-card-confirmation">@icon('trash')Remove card</button>
        </div>
        @else
            <p class="text-muted mb-0">No card on file.</p>
        @endif
        @unless($overview['ended'])
            @include('components.payment-card-preview', ['add' => true, 'modalId' => 'membership-card-editor'])
        @endunless
    </div>
    @unless($overview['ended'])
    @push('page-modals')
        @include('webapp.membership.edit.sections.billing.card-modal')
    @endpush
    <noscript><p class="text-danger mt-3">Please enable JavaScript to add a payment method.</p></noscript>
    @endunless
</section>
@if($savedCard)
@push('page-modals')
    @include('webapp.membership.edit.sections.billing.remove-card-modal')
@endpush
@endif
