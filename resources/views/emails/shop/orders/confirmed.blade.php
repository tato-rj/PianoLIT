@component('mail::message')
# Thank you for your order

Hi {{ $order->recipient['name'] }},

Your payment is confirmed.

**Order reference:** {{ $order->id }}

@component('mail::shop.products', ['items' => $order->items, 'currency' => $order->currency, 'showPrices' => true])
@endcomponent

@php($discount = app(\App\Services\Shop\FirstOrderDiscount::class)->amount($order->items))
Products: {{ app(\App\Services\Shop\Storefront::class)->money($order->subtotal + $discount, strtoupper($order->currency)) }}<br>
@if($discount)
{{ app(\App\Services\Shop\FirstOrderDiscount::class)->label($order->items) }}: −{{ app(\App\Services\Shop\Storefront::class)->money($discount, strtoupper($order->currency)) }}<br>
@endif
Delivery: {{ app(\App\Services\Shop\Storefront::class)->money($order->shipping, strtoupper($order->currency)) }}<br>
Tax: {{ app(\App\Services\Shop\Storefront::class)->money($order->tax, strtoupper($order->currency)) }}<br>
**Total paid: {{ app(\App\Services\Shop\Storefront::class)->money($order->total, strtoupper($order->currency)) }}**

@if($order->status === 'review_required' || in_array($order->printful_status, ['canceled', 'failed'], true))
Please check your order page for its latest status. Contact us if you have any questions.
@else
We’ll email you again when your package ships, with its tracking details. Items may arrive in separate packages.
@endif

@component('mail::button', ['url' => $trackingUrl])
Track your order
@endcomponent

Keep this private link to check your order on any device. You don’t need an account or a separate shopping app to use it.

Questions? Reply to this email or contact [contact@pianolit.com](mailto:contact@pianolit.com) with your order reference.

Thank you,<br>
The PianoLIT team
@endcomponent
