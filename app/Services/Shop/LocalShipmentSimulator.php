<?php

namespace App\Services\Shop;

use App\Shop\ShopOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Local state changes only; never signs or sends requests to the production webhook. */
class LocalShipmentSimulator
{
    public function eligible(ShopOrder $order): bool
    {
        return app()->environment('local') && $order->kind === 'merchandise' && $order->paid_at
            && in_array($order->mode, ['demo', 'sandbox'], true) && (int) $order->printful_store_id === 0
            && (!$order->printful_order_id || $order->printful_order_id === 'local-'.$order->id);
    }

    public function create(): ShopOrder
    {
        abort_unless(app()->environment('local'), 404);
        return DB::transaction(function () {
            $id = (string) Str::uuid();
            $order = ShopOrder::create(['id' => $id, 'kind' => 'merchandise', 'mode' => 'demo', 'printful_store_id' => 0,
                'owner_hash' => hash('sha256', Str::random(64)), 'status' => 'test_complete', 'bag_hash' => hash('sha256', $id),
                'printful_external_id' => str_replace('-', '', $id),
                'items' => [['product_id' => 0, 'sync_variant_id' => 900001, 'catalog_variant_id' => 0, 'quantity' => 1,
                    'unit_price' => 2800, 'name' => 'Sample piano T-shirt', 'option' => 'Mint / M', 'category' => 't-shirts',
                    'image' => \App\Support\ProfileUrl::base().'/images/shop/demo/shirt-mint.png']],
                'recipient' => ['name' => 'Jamie Sample', 'email' => 'jamie@example.test', 'address1' => '123 Sample Street',
                    'city' => 'Jersey City', 'state_code' => 'NJ', 'country_code' => 'US', 'zip' => '07302'],
                'currency' => 'usd', 'subtotal' => 2800, 'shipping' => 500, 'tax' => 0, 'total' => 3300, 'fulfillment_cost' => 0,
                'shipping_id' => 'STANDARD', 'shipping_name' => 'Sample delivery', 'expires_at' => now()->addDay(), 'paid_at' => now()]);
            app(OrderEmails::class)->confirmation($order);
            return $order;
        }, 3);
    }

    public function simulate(ShopOrder $order, string $type): bool
    {
        abort_unless($this->eligible($order), 404);
        abort_unless(in_array($type, ['shipment_sent', 'shipment_delivered'], true), 422);
        return DB::transaction(function () use ($order, $type) {
            $order = ShopOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($this->eligible($order), 404);
            $shipment = $order->shipments()->where('printful_id', '1')->first();
            if ($type === 'shipment_delivered' && !$shipment) {
                throw ValidationException::withMessages(['event' => 'Simulate shipping before delivery.']);
            }
            $receipt = hash('sha256', 'local-printful:'.$order->id.':1:'.$type);
            if (DB::table('printful_webhook_receipts')->where('id', $receipt)->exists()) {
                // Let an older local sample preview a newly introduced email type.
                return $type === 'shipment_delivered' && app(OrderEmails::class)->delivered($order->load('shipments'));
            }
            $at = now()->utc();
            $shipped = $shipment && $shipment->shipped_at ? $shipment->shipped_at : $at;
            $delivered = $type === 'shipment_delivered';
            $providerId = 'local-'.$order->id;
            $event = ['type' => $type, 'occurred_at' => $at->toIso8601String(), 'retries' => 0, 'store_id' => 0,
                'data' => ['order' => ['id' => $providerId, 'external_id' => $order->printful_external_id], 'shipment' => ['id' => 1]]];
            $events = [['description' => 'Sample package accepted by the carrier', 'triggered_at' => $shipped->toIso8601String()]];
            if ($delivered) $events[] = ['description' => 'Sample package delivered', 'triggered_at' => $at->toIso8601String()];
            $remote = ['id' => $providerId, 'external_id' => $order->printful_external_id, 'status' => 'fulfilled',
                'items' => array_map(fn ($item, $i) => ['id' => $i + 1, 'sync_variant_id' => $item['sync_variant_id']], $order->items, array_keys($order->items)),
                'shipments' => [['id' => 1, 'status' => $delivered ? 'delivered' : 'shipped', 'carrier' => 'Sample carrier',
                    'service' => 'Local test delivery', 'tracking_number' => 'LOCAL-'.strtoupper(substr($order->printful_external_id, 0, 12)),
                    'tracking_url' => null, 'shipped_at' => $shipped->toIso8601String(), 'delivered_at' => $delivered ? $at->toIso8601String() : null,
                    'estimated_delivery' => ['from_date' => $shipped->copy()->addDays(3)->toDateString(), 'to_date' => $shipped->copy()->addDays(5)->toDateString()],
                    'tracking_events' => $events,
                    'items' => array_map(fn ($item, $i) => ['item_id' => $i + 1, 'quantity' => $item['quantity']], $order->items, array_keys($order->items))]]];
            app(ShipmentUpdates::class)->simulate($order, $remote, $event, $receipt);
            $order->update(['submitted_at' => $order->submitted_at ?: $at]);
            return true;
        }, 3);
    }
}
