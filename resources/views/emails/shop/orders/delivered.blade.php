@component('mail::message')
# Your order arrived!

Hi {{ $order->recipient['name'] }},

Your PianoLIT order has been reported as delivered. We hope it brings a little more music into your day!

**Order reference:** {{ $order->id }}<br>
@if($deliveredAt)
**Delivered:** {{ $deliveredAt->format('F j, Y') }}
@endif

**Your items**

@component('mail::shop.products', ['items' => $order->items])
@endcomponent

@component('mail::button', ['url' => $trackingUrl])
View your order
@endcomponent

If you can’t find your package or need help, reply to this email or contact [contact@pianolit.com](mailto:contact@pianolit.com) with your order reference.

Thank you for shopping with PianoLIT,<br>
The PianoLIT team
@endcomponent
