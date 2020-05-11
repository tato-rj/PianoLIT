<tr>
	<td class="text-nowrap">{{carbon($invoice->next_payment_attempt)->toFormattedDateString()}}</td>
	<td class="text-nowrap">${{$invoice->amount_due/100}}</td>
</tr>