<?php

namespace App\Services\Shop;

use App\Shop\{eBook, eScore, ShopOrder};
use App\{User, Events\PurchaseMade};
use App\Merchandise\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DigitalCheckout
{
    public function product(string $type, int $id)
    {
        $models = ['ebooks' => eBook::class, 'escores' => eScore::class];
        abort_unless(isset($models[$type]), 404);
        return $models[$type]::published()->findOrFail($id);
    }

    public function snapshot(Request $request, string $type, int $id, ?string $coupon = null): array
    {
        $user = $request->user('web');
        abort_unless($user, 401);
        $product = $this->product($type, $id);
        if ($user->purchasesOf($product)->exists()) throw new CheckoutException('This edition is already in My downloads.');
        if ($product->isFree() || $user->isEligibleForFreeMonthlyProduct()) throw new CheckoutException('This edition is available without payment. Return to the product to download it.');
        $base = (int) $product->finalPrice(true);
        $discount = 0;
        if ($coupon) {
            if (app(CheckoutEnvironment::class)->mode() === 'demo') throw new CheckoutException('Coupon testing requires Stripe test mode.');
            $value = app(CheckoutStripe::class)->call('coupons', 'retrieve', [], strtoupper(trim($coupon)));
            if (empty($value['valid']) || !empty($value['applies_to']['products']) || (!empty($value['amount_off']) && ($value['currency'] ?? null) !== 'usd')) {
                throw new CheckoutException('This coupon does not apply to this edition.');
            }
            $discount = isset($value['percent_off']) ? (int) round($base * $value['percent_off'] / 100) : (int) ($value['amount_off'] ?? 0);
            $discount = max(0, min($base, $discount));
        }
        $item = ['product_type' => $type, 'product_id' => $id, 'sync_variant_id' => 0, 'quantity' => 1,
            'unit_price' => $base - $discount, 'base_price' => $base, 'coupon' => $coupon, 'discount' => $discount,
            'name' => $product->title, 'option' => ($type === 'ebooks' ? 'eBook' : 'eScore').' · Digital download',
            'category' => $type, 'image' => $product->mockup_image() ?: $product->cover_image()];
        return ['items' => [$item], 'currency' => 'USD', 'hash' => hash('sha256', json_encode([$item])), 'subtotal' => $item['unit_price']];
    }

    public function current(Request $request, ShopOrder $order): array
    {
        abort_unless($request->user('web') && (int) $request->user('web')->id === (int) $order->user_id, 404);
        $item = $order->items[0];
        return $this->snapshot($request, $item['product_type'], $item['product_id'], $item['coupon'] ?? null);
    }

    public function quote(Request $request): array
    {
        if (!app(CheckoutEnvironment::class)->available('digital')) throw new CheckoutException('Secure checkout is temporarily unavailable.');
        $data = $request->validate(['product_type' => 'required|in:ebooks,escores', 'product_id' => 'required|integer|min:1', 'coupon' => 'nullable|string|max:80']);
        $snapshot = $this->snapshot($request, $data['product_type'], $data['product_id'], $data['coupon'] ?? null);
        $recipient = app(ShopCheckout::class)->recipient($request);
        $recipient['email'] = $request->user('web')->email;
        $activeKey = hash('sha256', app(CheckoutEnvironment::class)->mode().':'.$request->user('web')->id.':'.$data['product_type'].':'.$data['product_id']);
        return DB::transaction(function () use ($request, $snapshot, $recipient, $activeKey) {
            User::whereKey($request->user('web')->id)->lockForUpdate()->firstOrFail();
            $existing = ShopOrder::where('active_key', $activeKey)->lockForUpdate()->first();
            if ($existing) {
                if ($existing->paid_at) return ['redirect' => route('shop.order', $existing->id)];
                if ($existing->stripe_payment_intent) {
                    $intent = app(CheckoutStripe::class)->call('paymentIntents', 'retrieve', [], $existing->stripe_payment_intent);
                    if (($intent['status'] ?? '') === 'succeeded') {
                        app(ShopCheckout::class)->paid($existing->id);
                        return ['redirect' => route('shop.order', $existing->id)];
                    }
                    if (!in_array($intent['status'] ?? '', ['requires_payment_method', 'requires_confirmation', 'canceled'], true)) {
                        throw new CheckoutException('This edition already has a payment in progress. Please check your order before starting another payment.');
                    }
                    if ($intent['status'] !== 'canceled') app(CheckoutStripe::class)->call('paymentIntents', 'cancel', [], $existing->stripe_payment_intent, $existing->id.':cancel');
                }
                $existing->update(['active_key' => null, 'status' => 'abandoned']);
            }
            return app(ShopCheckout::class)->saveQuote($request, $snapshot, $recipient, ['shipping' => 0, 'total' => 0],
                ['id' => 'DIGITAL', 'name' => 'Digital download'], [], 'digital', $activeKey);
        }, 3);
    }

    public function grant(ShopOrder $order, ?string $charge): void
    {
        if (!$order->paid_at || $order->purchase_id) return;
        $user = User::whereKey($order->user_id)->lockForUpdate()->first();
        $item = $order->items[0];
        $models = ['ebooks' => eBook::class, 'escores' => eScore::class];
        $product = isset($models[$item['product_type']]) ? $models[$item['product_type']]::find($item['product_id']) : null;
        if (!$user || !$product) { $order->update(['status' => 'review_required', 'last_error' => 'digital_delivery_unavailable']); return; }
        $purchase = $user->purchasesOf($product)->first();
        if (!$purchase) {
            $purchase = Purchase::withoutEvents(fn () => $user->purchases()->create(['item_type' => get_class($product),
                'item_id' => $product->id, 'cost' => $order->total / 100, 'charge_id' => $charge]));
            if ($order->mode === 'live') DB::afterCommit(function () use ($purchase) {
                try { event(new PurchaseMade($purchase)); } catch (\Throwable $exception) { /* Payment and delivery already succeeded. */ }
            });
        }
        $order->update(['purchase_id' => $purchase->id, 'status' => $order->mode === 'live' ? 'delivered' : 'test_complete']);
    }
}
