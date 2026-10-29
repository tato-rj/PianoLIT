<?php

namespace App\Mail\Shop;

use App\Shop\ShopOrder;
use App\Services\Shop\OrderTracking;
use Illuminate\Mail\Mailable;

class MerchandiseOrderDelivered extends Mailable
{
    public $order, $trackingUrl, $deliveredAt;

    public function __construct(ShopOrder $order, ?string $trackingUrl = null)
    {
        $this->order = $order;
        $this->trackingUrl = $trackingUrl ?? app(OrderTracking::class)->url($order);
        $this->deliveredAt = $order->shipments->max('delivered_at');
    }

    public function build()
    {
        return $this->subject('Your order arrived!')->markdown('emails.shop.orders.delivered');
    }
}
