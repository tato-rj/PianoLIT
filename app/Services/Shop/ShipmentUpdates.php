<?php

namespace App\Services\Shop;

use App\Shop\{ShopOrder, ShopShipment};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class ShipmentUpdates
{
    public function refresh(ShopOrder $order, ?array $event = null, ?string $receipt = null): void
    {
        $this->apply($order, app(PrintfulOrders::class)->tracking($order), $event, $receipt);
    }

    public function simulate(ShopOrder $order, array $remote, array $event, string $receipt): void
    {
        abort_unless(app(LocalShipmentSimulator::class)->eligible($order), 404);
        $this->apply($order, $remote, $event, $receipt, true);
    }

    private function apply(ShopOrder $order, array $remote, ?array $event, ?string $receipt, bool $simulation = false): void
    {
        if (($remote['external_id'] ?? null) !== $order->printful_external_id || empty($remote['id'])
            || ($order->printful_order_id && (string) $remote['id'] !== $order->printful_order_id)
            || ($event && (string) ($event['data']['order']['id'] ?? '') !== (string) $remote['id'])) {
            throw new CheckoutException('Shipment order identity mismatch.');
        }
        $shipments = $remote['shipments'] ?? [];
        if (!is_array($shipments) || count($shipments) > 100) throw new CheckoutException('Invalid shipment response.');
        $eventShipment = $event['data']['shipment']['id'] ?? null;
        if ($eventShipment !== null && !collect($shipments)->contains(fn ($shipment) => (string) ($shipment['id'] ?? '') === (string) $eventShipment)) {
            throw new CheckoutException('Shipment information is still being updated.');
        }
        $normalized = [];
        foreach ($shipments as $shipment) {
            $data = $this->normalize($shipment, $remote, $order);
            // Local HTTP links are generated here, never accepted from a provider payload.
            if ($simulation) $data['tracking_url'] = URL::signedRoute('shop.order.sample-package', ['order' => $order->id, 'shipment' => $data['printful_id']]);
            $normalized[] = $data;
        }
        DB::transaction(function () use ($order, $remote, $normalized, $event, $eventShipment, $receipt) {
            $locked = ShopOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($receipt && DB::table('printful_webhook_receipts')->where('id', $receipt)->exists()) return;
            $wasDelivered = app(OrderTracking::class)->fullyDelivered($locked);
            $locked->update(['printful_order_id' => (string) $remote['id'], 'printful_status' => $this->text($remote['status'] ?? '', 32),
                'tracking_checked_at' => now(), 'tracking_error' => null]);
            foreach ($normalized as $data) {
                $shipment = ShopShipment::firstOrNew(['order_id' => $order->id, 'printful_id' => $data['printful_id']]);
                $delivered = $shipment->status === 'delivered' || $data['status'] === 'delivered'
                    || ($event && $event['type'] === 'shipment_delivered' && $data['printful_id'] === (string) $eventShipment);
                if ($delivered) {
                    $data['status'] = 'delivered';
                    $data['delivered_at'] = $shipment->delivered_at ?: ($data['delivered_at'] ?: $this->date($event['data']['shipment']['delivered_at'] ?? $event['occurred_at'] ?? null));
                }
                $shipment->fill($data)->save();
                if (in_array($shipment->status, ['shipped', 'delivered'], true)) app(OrderEmails::class)->shipping($locked, $shipment);
            }
            $locked->unsetRelation('shipments');
            // Only a new complete-delivery transition creates an arrival email.
            // Existing delivered orders are not backfilled by later polling.
            if (!$wasDelivered) app(OrderEmails::class)->delivered($locked);
            if ($receipt) DB::table('printful_webhook_receipts')->insert(['id' => $receipt, 'order_id' => $order->id, 'type' => $event['type'], 'created_at' => now()]);
        }, 3);
    }

    private function normalize(array $shipment, array $remote, ShopOrder $order): array
    {
        if (!preg_match('/^[1-9][0-9]{0,19}$/D', (string) ($shipment['id'] ?? ''))) throw new CheckoutException('Invalid shipment identity.');
        $items = [];
        foreach ($shipment['items'] ?? [] as $item) {
            $providerItem = collect($remote['items'] ?? [])->firstWhere('id', $item['item_id'] ?? null);
            $saved = $providerItem ? collect($order->items)->firstWhere('sync_variant_id', $providerItem['sync_variant_id'] ?? null) : null;
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 999]]);
            if (!$saved || !$quantity || $quantity > $saved['quantity']) throw new CheckoutException('Shipment items do not match the purchased products.');
            $items[] = ['name' => $saved['name'], 'option' => $saved['option'], 'quantity' => $quantity];
        }
        $status = in_array($shipment['status'] ?? '', ['delivered', 'returned', 'canceled'], true) ? $shipment['status'] : 'shipped';
        return ['printful_id' => (string) $shipment['id'], 'status' => $status,
            'carrier' => $this->text($shipment['carrier'] ?? '', 100), 'service' => $this->text($shipment['service'] ?? '', 150),
            'tracking_number' => $this->text($shipment['tracking_number'] ?? '', 200), 'tracking_url' => $this->trackingUrl($shipment['tracking_url'] ?? null),
            'items' => $items, 'shipped_at' => $this->date($shipment['shipped_at'] ?? $shipment['ship_date'] ?? $shipment['created'] ?? null),
            'estimated_from' => $this->date($shipment['estimated_delivery']['from_date'] ?? null),
            'estimated_to' => $this->date($shipment['estimated_delivery']['to_date'] ?? null),
            'events' => collect($shipment['tracking_events'] ?? [])->take(50)->map(fn ($event) => [
                'description' => $this->text($event['description'] ?? '', 500), 'at' => optional($this->date($event['triggered_at'] ?? null))->toIso8601String(),
            ])->filter(fn ($event) => $event['description'] !== '')->values()->all(),
            'delivered_at' => $this->date($shipment['delivered_at'] ?? null)];
    }

    public function trackingUrl($url): ?string
    {
        if (!is_string($url) || strlen($url) > 2000 || !filter_var($url, FILTER_VALIDATE_URL)) return null;
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 443)
            || empty($parts['host']) || strpos($parts['host'], '.') === false || filter_var($parts['host'], FILTER_VALIDATE_IP)
            || preg_match('/\.(test|local|localhost|internal)$/i', $parts['host'])) return null;
        return $url;
    }

    private function text($value, int $length): string
    {
        return is_scalar($value) ? mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $value)), 0, $length) : '';
    }

    private function date($value): ?Carbon
    {
        if (!$value) return null;
        try { return is_numeric($value) ? Carbon::createFromTimestampUTC((int) $value) : Carbon::parse($value)->utc(); }
        catch (\Throwable $exception) { return null; }
    }
}
