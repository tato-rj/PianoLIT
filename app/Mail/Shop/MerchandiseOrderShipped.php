<?php

namespace App\Mail\Shop;

use App\Shop\{ShopOrder, ShopShipment};
use App\Services\Shop\OrderTracking;
use Illuminate\Mail\Mailable;

class MerchandiseOrderShipped extends Mailable
{
    public $order, $shipment, $trackingUrl;

    public function __construct(ShopOrder $order, ShopShipment $shipment, ?string $trackingUrl = null)
    {
        $this->order = $order;
        $this->shipment = $shipment;
        $this->trackingUrl = $trackingUrl ?? app(OrderTracking::class)->url($order);
    }

    public function build()
    {
        return $this->subject('Your PianoLIT order is on its way')->markdown('emails.shop.orders.shipped');
    }
}
