<div class="tab-pane fade" id="invoices-tab" role="tabpanel">
	<div class="mb-3">
		<h5>Upcoming Invoice</h5>
		@table([
			'id' => 'invoices-table',
			'headers' => ['Date', 'Charge'],
			'rows' => view('webapp.membership.edit.sections.invoices.upcoming', [
				'invoice' => auth()->user()->membership->source->upcomingInvoice()
			])
		])
	</div>
	
	<div>
		<h5>Payment History</h5>
		@table([
			'id' => 'invoices-table',
			'headers' => ['Date', 'Payment', 'Status', 'Invoice'],
			'rows' => view('webapp.membership.edit.sections.invoices.past', [
				'invoices' => auth()->user()->membership->source->pastInvoices()
			])
		])
	</div>	
</div>