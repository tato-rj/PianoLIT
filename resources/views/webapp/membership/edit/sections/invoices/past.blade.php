@foreach($invoices as $invoice)
@if($invoice->amount_due)
  <tr>
    <td class="text-nowrap">{{$invoice->status == 'paid' ? carbon($invoice->status_transitions->paid_at)->toFormattedDateString() : 'unknown'}}</td>
    <td class="text-nowrap">${{$invoice->amount_due / 100}}</td>
    <td>{{ucfirst($invoice->status)}}</td>
    <td><a class="link-blue" target="_blank" href="{{$invoice->hosted_invoice_url}}">View</a></td>
  </tr>
 @endif
@endforeach