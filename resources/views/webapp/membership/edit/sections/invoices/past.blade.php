@forelse($invoices as $invoice)
    <tr>
        <td class="text-nowrap">{{ !empty($invoice['created']) ? carbon($invoice['created'])->toFormattedDateString() : 'Not available' }}</td>
        <td class="text-nowrap">{{ strtoupper($invoice['currency']) }} {{ number_format(($invoice['status'] === 'paid' ? $invoice['amount_paid'] : $invoice['amount_due']) / 100, 2) }}</td>
        <td>{{ ucfirst($invoice['status']) }}</td>
        <td>
            @if(!empty($invoice['hosted_invoice_url']) && preg_match('~^https://invoice\.stripe\.com/~', $invoice['hosted_invoice_url']))
                <a class="link-blue" target="_blank" rel="noopener noreferrer" href="{{ $invoice['hosted_invoice_url'] }}">{{ $invoice['status'] === 'open' ? 'Pay / View' : 'View' }}</a>
            @else
                <span class="text-muted">Not available</span>
            @endif
        </td>
    </tr>
@empty
    <tr><td colspan="4" class="text-muted">No invoices yet.</td></tr>
@endforelse
