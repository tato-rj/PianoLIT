<button type="button"
    class="btn btn-primary btn-sm px-3 px-sm-4 py-2 d-inline-flex align-items-center gap-2 fw-semibold shadow-none"
    style="--radius-button: var(--radius-pill)"
    @unless($preview ?? false) show="subscription-modal" data-view="newsletter-subscription" data-url="{{ route('subscriptions.modal') }}" @endunless>
    <span class="d-inline-flex" aria-hidden="true">@icon('mail', ['mr' => 0])</span>
    {{ $label ?? 'Subscribe' }}
</button>
