<div class="tab-pane fade" id="invoices-tab" role="tabpanel" data-membership-invoices data-url="{{ route('webapp.membership.invoices') }}">
    <p data-invoice-status role="status" aria-live="polite">Open this tab to load your invoices.</p>
    <div data-invoice-content></div>
    <button type="button" class="btn btn-outline-secondary" data-invoice-retry hidden>Try again</button>
    <button type="button" class="btn btn-outline-secondary" data-invoice-more hidden>Older invoices</button>
</div>
