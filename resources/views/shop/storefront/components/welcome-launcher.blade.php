<button type="button" class="btn btn-primary {{ ($preview ?? false) ? '' : 'shop-welcome-launcher' }}"
    @if($preview ?? false) data-ds-preview @else data-shop-welcome-launcher hidden aria-haspopup="dialog" aria-controls="modal-newsletter-subscription" @endif>
    @icon('gift'){{ $offerAvailable ? 'Get 10% off' : 'PianoLIT newsletter' }}
</button>
