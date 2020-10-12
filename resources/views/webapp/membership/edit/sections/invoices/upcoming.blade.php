<tr>
	@if($invoice)
	<td class="text-nowrap">{{carbon($invoice->next_payment_attempt)->toFormattedDateString()}}</td>
	<td class="text-nowrap">${{$invoice->amount_due/100}}</td>
	@else
	<td class="text-nowrap text-muted" colspan="2"><i>No upcoming invoices</i></td>
	@endif
</tr>
