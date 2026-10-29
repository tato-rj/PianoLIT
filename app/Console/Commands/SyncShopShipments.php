<?php

namespace App\Console\Commands;

use App\Shop\ShopOrder;
use App\Services\Shop\{Printful, ShipmentUpdates};
use Illuminate\Console\Command;

class SyncShopShipments extends Command
{
    protected $signature = 'shop:sync-shipments';
    protected $description = 'Recover missing shipment webhooks through read-only Printful order checks';

    public function handle(ShipmentUpdates $updates)
    {
        if (!app()->environment('production') || !app(Printful::class)->enabled()) { $this->info('Live shipment checks remain disabled.'); return 0; }
        $deadline = microtime(true) + 45;
        $orders = ShopOrder::where('kind', 'merchandise')->where('mode', 'live')->whereNotNull('paid_at')->whereNotNull('submitted_at')
            ->where('printful_store_id', app(Printful::class)->storeId())->where('paid_at', '>=', now()->subDays(180))
            ->where(fn ($query) => $query->whereNull('printful_status')->orWhereNotIn('printful_status', ['canceled', 'failed']))
            ->where(fn ($query) => $query->whereNull('tracking_checked_at')->orWhere('tracking_checked_at', '<', now()->subMinutes(15)))
            ->where(fn ($query) => $query->where('printful_status', '!=', 'fulfilled')->orWhereNull('printful_status')->orWhereDoesntHave('shipments')->orWhereHas('shipments', fn ($shipments) => $shipments->where('status', '!=', 'delivered')))
            ->orderBy('tracking_checked_at')->limit(20)->get();
        foreach ($orders as $order) {
            if (microtime(true) > $deadline) break;
            try { $updates->refresh($order); }
            catch (\Throwable $exception) { $order->update(['tracking_checked_at' => now(), 'tracking_error' => class_basename($exception)]); }
        }
        $this->info('Shipment checks complete.');
        return 0;
    }
}
