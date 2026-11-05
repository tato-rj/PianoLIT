<button type="button" class="btn btn-primary rounded-0 {{ ($preview ?? false) ? '' : 'shop-welcome-launcher' }}" style="--radius-button: 0"
    @if($preview ?? false) data-ds-preview @else data-shop-welcome-launcher hidden aria-haspopup="dialog" aria-controls="modal-newsletter-subscription" @endif>
    @icon('tag')Get 10% off
</button>
