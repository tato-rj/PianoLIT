<tr>
    @if($invoice)
        <td class="text-nowrap">{{ !empty($invoice['next_payment_attempt']) ? carbon($invoice['next_payment_attempt'])->toFormattedDateString() : (!empty($invoice['period_end']) ? carbon($invoice['period_end'])->toFormattedDateString() : 'At your next renewal') }}</td>
        <td class="text-nowrap">{{ strtoupper($invoice['currency']) }} {{ number_format($invoice['amount_due'] / 100, 2) }}</td>
    @else
        <td class="text-nowrap text-muted" colspan="2"><i>No upcoming invoices</i></td>
    @endif
</tr>
