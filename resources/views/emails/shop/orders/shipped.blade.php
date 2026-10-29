@component('mail::message')
# Your order is on its way

Hi {{ $order->recipient['name'] }},

A package from your PianoLIT order has shipped!

**Order reference:** {{ $order->id }}<br>
@if($shipment->carrier)**Carrier:** {{ $shipment->carrier }}<br>@endif
@if($shipment->service)**Delivery service:** {{ $shipment->service }}<br>@endif
@if($shipment->tracking_number)**Tracking number:** {{ $shipment->tracking_number }}<br>@endif
@if($shipment->shipped_at)**Shipped:** {{ $shipment->shipped_at->format('F j, Y') }}@endif

@if($shipment->estimated_from && $shipment->estimated_to)
**Estimated delivery:** {{ $shipment->estimated_from->format('F j') }}–{{ $shipment->estimated_to->format('F j, Y') }}
@endif

@if($shipment->items)
**In this package**

@foreach($shipment->items as $item)
- {{ $item['name'] }} · {{ $item['option'] }} × {{ $item['quantity'] }}
@endforeach
@endif

@if($shipment->tracking_url)
@component('mail::button', ['url' => $shipment->tracking_url])
Track this package
@endcomponent

Tracking can take a little time to update after the carrier receives your package.
@else
The carrier hasn’t provided a tracking link yet. Your order page will show the latest available details.
@endif

See all packages and updates on [your order page]({{ $trackingUrl }}). If your order ships in separate packages, we’ll send a shipping email for each one.

Questions? Reply to this email or contact [contact@pianolit.com](mailto:contact@pianolit.com) with your order reference.

Enjoy the music,<br>
The PianoLIT team
@endcomponent
