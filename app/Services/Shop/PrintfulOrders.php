<?php

namespace App\Services\Shop;

use App\Shop\ShopOrder;
use Illuminate\Support\Facades\Cache;

/** All non-live modes simulate fulfillment, even when a real credential exists. */
class PrintfulOrders extends Printful
{
    private function live(): bool
    {
        if (app(CheckoutEnvironment::class)->mode() !== 'live') return false;
        if (!app()->environment('production') || !config('shop-checkout.printful_orders_enabled') || !$this->enabled()) throw new CheckoutException('Real fulfillment is disabled in this environment.');
        return true;
    }

    public function countries(): array
    {
        if (!$this->live()) return json_decode(file_get_contents(resource_path('data/shop-checkout-countries.json')), true, 512, JSON_THROW_ON_ERROR);
        return Cache::remember('shop:printful:countries:'.$this->storeId(), 3600, function () {
            return $this->request('GET', 'countries')['result'];
        });
    }

    public function rates(array $recipient, array $items, string $currency): array
    {
        if (!$this->live()) return [
            ['id' => 'STANDARD', 'name' => 'Standard delivery', 'rate' => '5.00', 'currency' => $currency],
            ['id' => 'EXPRESS', 'name' => 'Express delivery', 'rate' => '12.00', 'currency' => $currency],
        ];
        return $this->request('POST', 'shipping/rates', ['recipient' => $recipient, 'currency' => $currency,
            'items' => array_map(fn ($item) => ['variant_id' => $item['catalog_variant_id'], 'quantity' => $item['quantity']], $items)])['result'];
    }

    public function estimate(array $recipient, array $items, string $currency, string $shipping): array
    {
        if (!$this->live()) {
            $cost = array_sum(array_map(fn ($item) => (int) round($item['unit_price'] * .6) * $item['quantity'], $items));
            $delivery = $shipping === 'EXPRESS' ? 1200 : 500;
            return ['shipping' => $delivery, 'total' => $cost + $delivery];
        }
        $result = $this->request('POST', 'orders/estimate-costs', $this->body($recipient, $items, $currency, $shipping))['result'];
        if (strtoupper($result['costs']['currency'] ?? '') !== $currency) throw new CheckoutException('Delivery costs could not be confirmed.');
        return ['shipping' => $this->cents($result['costs']['shipping'] ?? null), 'total' => $this->cents($result['costs']['total'] ?? null)];
    }

    public function body(array $recipient, array $items, string $currency, string $shipping): array
    {
        return ['recipient' => $recipient, 'shipping' => $shipping,
            'items' => array_map(fn ($item) => ['sync_variant_id' => $item['sync_variant_id'], 'quantity' => $item['quantity']], $items),
            'retail_costs' => ['currency' => $currency]];
    }

    public function existing(ShopOrder $order): ?array
    {
        $this->authorize($order);
        $body = $this->request('GET', 'orders/@'.$order->printful_external_id, [], 15, true);
        return $body ? $body['result'] : null;
    }

    public function tracking(ShopOrder $order): array
    {
        // Read-only tracking continues when new sales or order confirmation are disabled.
        if ($order->mode !== 'live' || $order->kind !== 'merchandise' || !$order->paid_at
            || !app()->environment('production') || !$this->enabled() || (string) $order->printful_store_id !== $this->storeId()) {
            throw new CheckoutException('Live shipment checks are disabled in this environment.');
        }
        $remote = $this->request('GET', 'orders/@'.$order->printful_external_id)['result'];
        if (!preg_match('/^[1-9][0-9]*$/D', (string) ($remote['id'] ?? ''))) throw new CheckoutException('Invalid fulfillment order.');
        if (($remote['external_id'] ?? null) !== $order->printful_external_id || ($order->printful_order_id && (string) $remote['id'] !== $order->printful_order_id)) throw new CheckoutException('Shipment order identity mismatch.');
        $details = $this->request('GET', 'v2/orders/'.$remote['id'].'/shipments', ['limit' => 100], 15, true);
        if ($details) {
            if (!isset($details['data']) || !is_array($details['data'])) throw new CheckoutException('Invalid shipment details response.');
            $shipments = $details['data'];
            if (count($shipments) > 100 || (int) ($details['paging']['total'] ?? count($shipments)) > count($shipments)) throw new CheckoutException('Shipment details are incomplete.');
            foreach ($shipments as $shipment) {
                if ((string) ($shipment['order_id'] ?? '') !== (string) $remote['id'] || ($shipment['order_external_id'] ?? null) !== $order->printful_external_id) throw new CheckoutException('Shipment order identity mismatch.');
            }
            $byId = collect($shipments)->keyBy('id');
            $remote['shipments'] = array_map(function ($shipment) use ($byId) {
                $detail = $byId->get($shipment['id']);
                if (!$detail) return $shipment;
                return array_merge($shipment, array_intersect_key($detail, array_flip(['carrier', 'service', 'tracking_url', 'tracking_number', 'shipped_at', 'delivered_at', 'estimated_delivery', 'tracking_events'])),
                    ['status' => ($detail['delivery_status'] ?? '') === 'delivered' ? 'delivered' : (($detail['shipment_status'] ?? '') === 'canceled' ? 'canceled' : (($detail['delivery_status'] ?? '') === 'returned' ? 'returned' : 'shipped'))]);
            }, $remote['shipments'] ?? []);
        }
        return $remote;
    }

    public function draft(ShopOrder $order): array
    {
        $this->authorize($order);
        return $this->request('POST', 'orders', ['external_id' => $order->printful_external_id]
            + $this->body($order->recipient, $order->items, strtoupper($order->currency), $order->shipping_id))['result'];
    }

    public function confirm(ShopOrder $order, string $id): array
    {
        $this->authorize($order);
        if (!preg_match('/^[1-9][0-9]*$/D', $id)) throw new CheckoutException('Invalid fulfillment order.');
        return $this->request('POST', 'orders/'.$id.'/confirm')['result'];
    }

    private function authorize(ShopOrder $order): void
    {
        if (!$order->paid_at || !app(CheckoutEnvironment::class)->canFulfill($order)) throw new CheckoutException('Real fulfillment is disabled in this environment.');
    }

    public function cents($amount): int
    {
        if (!is_scalar($amount) || !preg_match('/^[0-9]{1,7}(\.[0-9]{1,2})?$/D', (string) $amount)) throw new CheckoutException('Delivery costs could not be confirmed.');
        $parts = explode('.', (string) $amount);
        return (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
    }
}
