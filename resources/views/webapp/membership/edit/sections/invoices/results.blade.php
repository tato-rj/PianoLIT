<div class="mb-4">
    <h5>Upcoming Invoice</h5>
    @table(['id' => 'upcoming-invoice-table', 'headers' => ['Date', 'Charge'], 'rows' => view('webapp.membership.edit.sections.invoices.upcoming', ['invoice' => $upcoming])])
</div>
<div>
    <h5>Payment History</h5>
    @table(['id' => 'past-invoices-table', 'headers' => ['Date', 'Payment', 'Status', 'Invoice'], 'rows' => view('webapp.membership.edit.sections.invoices.past', compact('invoices'))])
</div>
