<?php

namespace App\Services\Shop;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** A browser-session wish-to-purchase bag, separate from digital purchases. */
class MerchandiseBag
{
    const SESSION_KEY = 'merchandise_bag';
    const MAX_QUANTITY = 10;
    const MAX_LINES = 40;

    public function contents(Request $request): array
    {
        $saved = $request->session()->get(self::SESSION_KEY, []);
        $saved = is_array($saved) ? array_slice($saved, 0, self::MAX_LINES, true) : [];
        $catalog = app(Storefront::class);
        $ids = array_column($saved, 'product_id');
        $products = $catalog->query()->with('images')->whereIn('id', $ids)->get()->keyBy('id');
        $lines = [];
        $currency = null;
        $subtotal = 0;
        foreach ($saved as $key => $item) {
            $product = $products->get($item['product_id'] ?? null);
            if (!$product) continue;
            $variant = collect($catalog->variants($product))->firstWhere('id', $item['variant_id'] ?? null);
            if (!$variant || ($currency && $currency !== $variant['currency'])) continue;
            $quantity = max(1, min(self::MAX_QUANTITY, (int) ($item['quantity'] ?? 1)));
            $currency = $variant['currency'];
            $subtotal += $variant['cents'] * $quantity;
            $lines[$key] = ['product' => $catalog->present($product), 'variant' => $variant, 'quantity' => $quantity,
                'total' => $catalog->money($variant['cents'] * $quantity, $currency)];
        }
        return ['lines' => $lines, 'count' => array_sum(array_column($lines, 'quantity')),
            'currency' => $currency, 'subtotal' => $catalog->money($subtotal, $currency ?? 'USD'),
            'unavailable' => count($saved) - count($lines)];
    }

    public function put(Request $request, int $productId, int $variantId, int $quantity, bool $add = false): array
    {
        $bag = $this->contents($request);
        $catalog = app(Storefront::class);
        $product = $catalog->query()->findOrFail($productId);
        $variant = collect($catalog->variants($product))->firstWhere('id', $variantId);
        if (!$variant) throw ValidationException::withMessages(['variant_id' => 'This option is no longer available. Please choose another.'])->errorBag('storefront');
        if ($bag['currency'] && $bag['currency'] !== $variant['currency']) {
            throw ValidationException::withMessages(['variant_id' => 'Please keep products in the same currency in your bag.'])->errorBag('storefront');
        }
        $key = $productId.':'.$variantId;
        if (!isset($bag['lines'][$key]) && count($bag['lines']) >= self::MAX_LINES) {
            throw ValidationException::withMessages(['quantity' => 'Your bag can hold up to 40 different options.'])->errorBag('storefront');
        }
        if ($add) $quantity += $bag['lines'][$key]['quantity'] ?? 0;
        if ($quantity > self::MAX_QUANTITY) {
            throw ValidationException::withMessages(['quantity' => 'You can add up to 10 of each option.'])->errorBag('storefront');
        }
        $saved = [];
        foreach ($bag['lines'] as $lineKey => $line) {
            $saved[$lineKey] = ['product_id' => $line['product']['id'], 'variant_id' => $line['variant']['id'], 'quantity' => $line['quantity']];
        }
        $saved[$key] = ['product_id' => $productId, 'variant_id' => $variantId, 'quantity' => $quantity];
        $request->session()->put(self::SESSION_KEY, $saved);
        return $this->contents($request);
    }
}
