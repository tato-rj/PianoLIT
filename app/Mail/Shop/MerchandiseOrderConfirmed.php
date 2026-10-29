<?php

namespace App\Mail\Shop;

use App\Shop\ShopOrder;
use App\Services\Shop\OrderTracking;
use Illuminate\Mail\Mailable;

class MerchandiseOrderConfirmed extends Mailable
{
    public $order, $trackingUrl;

    public function __construct(ShopOrder $order, ?string $trackingUrl = null)
    {
        $this->order = $order;
        $this->trackingUrl = $trackingUrl ?? app(OrderTracking::class)->url($order);
    }

    public function build()
    {
        return $this->subject('Thank you for your PianoLIT order')->markdown('emails.shop.orders.confirmed');
    }
}
