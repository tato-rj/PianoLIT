<?php

namespace App\Services\Shop;

use App\Shop\ShopOrder;
use Illuminate\Support\Facades\URL;

class OrderTracking
{
    public function url(ShopOrder $order): string
    {
        // A read-only bearer link for this order, usable by a guest on another device.
        return URL::signedRoute('shop.order.track', ['order' => $order->id]);
    }

    public function visible()
    {
        return ShopOrder::where('kind', 'merchandise')->whereNotNull('paid_at')
            ->when(app()->environment('local'), fn ($query) => $query->whereIn('mode', ['demo', 'sandbox']), fn ($query) => $query->where('mode', 'live'));
    }

    public function status(ShopOrder $order): string
    {
        if ($order->status === 'review_required') return 'Order under review';
        if ($order->printful_status === 'canceled') return 'Canceled';
        if ($order->printful_status === 'failed') return 'Fulfillment needs attention';
        $shipments = $order->shipments;
        if ($this->fullyDelivered($order)) return 'Delivered';
        if ($shipments->contains('status', 'returned')) return 'Package returned';
        if ($shipments->isNotEmpty()) return $order->printful_status === 'partial' ? 'Partially shipped' : 'Shipped';
        return $order->submitted_at ? 'Preparing your order' : 'Payment confirmed';
    }

    public function fullyDelivered(ShopOrder $order): bool
    {
        return $order->printful_status === 'fulfilled' && $order->shipments->isNotEmpty()
            && $order->shipments->every(fn ($shipment) => $shipment->status === 'delivered');
    }
}
