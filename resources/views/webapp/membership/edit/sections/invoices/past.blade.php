@foreach($invoices as $invoice)
@if($invoice->due_date)
  <tr>
    <td class="text-nowrap">{{$invoice->due_date}}</td>
    <td class="text-nowrap">${{$invoice->amount_paid / 100}}</td>
    <td>{{ucfirst($invoice->status)}}</td>
    <td><a class="link-blue" target="_blank" href="{{$invoice->hosted_invoice_url}}">View</a></td>
  </tr>
 @endif
@endforeach