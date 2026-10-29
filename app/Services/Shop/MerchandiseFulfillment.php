<?php

namespace App\Services\Shop;

use App\Shop\ShopOrder;
use Illuminate\Support\Facades\DB;

class MerchandiseFulfillment
{
    public function process(string $id): void
    {
        try {
            DB::transaction(function () use ($id) {
                $order = ShopOrder::whereKey($id)->lockForUpdate()->firstOrFail();
                if (!$order->paid_at || $order->submitted_at || $order->status === 'review_required') return;
                if ($order->kind !== 'merchandise') return;
                if (!app(CheckoutEnvironment::class)->canFulfill($order)) {
                    if ($order->mode === 'sandbox') $order->update(['status' => 'test_complete']);
                    return;
                }
                $verified = app(ShopCheckout::class)->paid($id); // Recheck payment/refunds before any fulfillment mutation.
                if ($verified->status === 'review_required') return;
                $provider = app(PrintfulOrders::class);
                $draft = $provider->existing($order) ?? $provider->draft($order);
                if (($draft['external_id'] ?? null) !== $order->printful_external_id || empty($draft['id'])) throw new CheckoutException('Fulfillment order identity mismatch.');
                $expected = array_map(fn ($item) => [(int) $item['sync_variant_id'], (int) $item['quantity']], $order->items);
                $actual = array_map(fn ($item) => [(int) ($item['sync_variant_id'] ?? 0), (int) ($item['quantity'] ?? 0)], $draft['items'] ?? []);
                sort($expected); sort($actual);
                if ($actual !== $expected) throw new CheckoutException('Fulfillment items do not match the paid order.');
                foreach (['address1', 'address2', 'city', 'state_code', 'country_code', 'zip'] as $field) {
                    if (mb_strtolower(trim($draft['recipient'][$field] ?? '')) !== mb_strtolower(trim($order->recipient[$field] ?? ''))) {
                        throw new CheckoutException('Fulfillment delivery address does not match the paid order.');
                    }
                }
                $order->update(['printful_order_id' => (string) $draft['id']]);
                if (($draft['status'] ?? null) === 'draft') {
                    if ($provider->cents($draft['costs']['total'] ?? null) > $order->fulfillment_cost
                        || strtoupper($draft['costs']['currency'] ?? '') !== strtoupper($order->currency)) {
                        $order->update(['status' => 'review_required', 'last_error' => 'fulfillment_cost_changed']);
                        return;
                    }
                    $draft = $provider->confirm($order, (string) $draft['id']);
                }
                if (!in_array($draft['status'] ?? null, ['pending', 'inprocess', 'inreview', 'onhold', 'partial', 'fulfilled'], true)) throw new CheckoutException('Fulfillment has not accepted the order.');
                $order->update(['status' => 'submitted', 'submitted_at' => now(), 'last_error' => null]);
            });
        } catch (\Throwable $exception) {
            // No customer address, upstream body, credential or client secret in diagnostics.
            ShopOrder::whereKey($id)->whereNull('submitted_at')->increment('attempts', 1,
                ['last_error' => class_basename($exception), 'updated_at' => now()]);
        }
    }
}
