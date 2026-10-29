<?php

namespace App\Services\Shop;

use App\Shop\ShopOrder;
use App\Merchandise\MerchandiseProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShopCheckout
{
    public function owner(Request $request): string
    {
        if (!$request->session()->has('shop_checkout_owner')) $request->session()->put('shop_checkout_owner', Str::random(64));
        return hash('sha256', $request->session()->get('shop_checkout_owner'));
    }

    public function owned(Request $request, string $id): ShopOrder
    {
        $order = ShopOrder::findOrFail($id);
        abort_unless($order->user_id
            ? ($request->user('web') && (int) $order->user_id === (int) $request->user('web')->id)
            : hash_equals($order->owner_hash, $this->owner($request)), 404);
        abort_unless($order->mode === app(CheckoutEnvironment::class)->mode(), 404);
        return $order;
    }

    public function countries(string $kind = 'merchandise'): array
    {
        if ($kind === 'digital') {
            $countries = json_decode(file_get_contents(resource_path('data/country-continents.json')), true);
            return collect($countries)->map(fn ($value, $code) => ['code' => $code, 'name' => $value['name'], 'states' => null])->sortBy('name')->values()->all();
        }
        $allowed = config('shop-checkout.countries', []);
        return array_values(array_filter(app(PrintfulOrders::class)->countries(), fn ($country) => !$allowed || in_array($country['code'], $allowed, true)));
    }

    public function recipient(Request $request): array
    {
        $data = $request->validate(['email' => 'required|email:rfc|max:254', 'name' => 'required|string|max:100',
            'address1' => 'required|string|max:150', 'address2' => 'nullable|string|max:150', 'city' => 'required|string|max:100',
            'state_code' => 'nullable|string|max:32', 'country_code' => 'required|string|size:2', 'zip' => 'nullable|string|max:20',
            'shipping_id' => 'nullable|string|max:100']);
        $recipient = array_map(fn ($value) => trim((string) $value), array_intersect_key($data, array_flip(['email', 'name', 'address1', 'address2', 'city', 'state_code', 'country_code', 'zip'])));
        $recipient['country_code'] = strtoupper($recipient['country_code']);
        $country = collect($this->countries($request->input('kind') === 'digital' ? 'digital' : 'merchandise'))->firstWhere('code', $recipient['country_code']);
        if (!$country) throw ValidationException::withMessages(['country_code' => 'Please choose an available delivery country.']);
        if (!empty($country['states']) && !in_array($recipient['state_code'] ?? '', array_column($country['states'], 'code'), true)) {
            throw ValidationException::withMessages(['state_code' => 'Please choose a state or province for this country.']);
        }
        foreach (['name', 'address1', 'city'] as $field) if ($recipient[$field] === '') throw ValidationException::withMessages([$field => 'Please complete your delivery address.']);
        return $recipient;
    }

    public function snapshot(Request $request): array
    {
        $bag = app(MerchandiseBag::class)->contents($request);
        if (!$bag['lines']) throw new CheckoutException('Your bag is empty. Add a product before checking out.');
        if ($bag['currency'] !== 'USD') throw new CheckoutException('This checkout currently accepts USD products. Please contact us for help.');
        $items = [];
        foreach ($bag['lines'] as $line) {
            $product = app(Storefront::class)->query()->findOrFail($line['product']['id']);
            $raw = collect($product->variants)->firstWhere('id', $line['variant']['id']);
            if (empty($raw['variant_id']) && app(CheckoutEnvironment::class)->mode() === 'live') throw new CheckoutException('An item needs a product refresh before it can be purchased.');
            $items[] = ['product_id' => $product->id, 'sync_variant_id' => $line['variant']['id'],
                'catalog_variant_id' => (int) $raw['variant_id'], 'quantity' => $line['quantity'],
                'unit_price' => $line['variant']['cents'], 'name' => $product->name,
                'option' => $line['variant']['color'].' / '.$line['variant']['size'], 'category' => $line['product']['category'],
                'image' => $line['product']['imageUrl']];
        }
        return ['items' => $items, 'currency' => $bag['currency'], 'hash' => hash('sha256', json_encode($items)),
            'subtotal' => array_sum(array_map(fn ($item) => $item['unit_price'] * $item['quantity'], $items))];
    }

    public function quote(Request $request): array
    {
        if ($request->input('kind') === 'digital') return app(DigitalCheckout::class)->quote($request);
        if (!app(CheckoutEnvironment::class)->available()) throw new CheckoutException('Merchandise checkout is temporarily unavailable.');
        $mode = app(CheckoutEnvironment::class)->mode();
        $recipient = $this->recipient($request);
        $snapshot = $this->snapshot($request);
        $provider = app(PrintfulOrders::class);
        $rates = array_values(array_filter($provider->rates($recipient, $snapshot['items'], $snapshot['currency']),
            fn ($rate) => strtoupper($rate['currency'] ?? '') === $snapshot['currency'] && !empty($rate['id'])));
        if (!$rates) throw new CheckoutException('These items cannot currently be delivered to this address. Please try another address.');
        $selected = $request->input('shipping_id');
        $rate = $selected ? collect($rates)->firstWhere('id', $selected) : $rates[0];
        if (!$rate) throw new CheckoutException('Please choose an available delivery option.');
        $estimate = $provider->estimate($recipient, $snapshot['items'], $snapshot['currency'], $rate['id']);
        return $this->saveQuote($request, $snapshot, $recipient, $estimate, $rate, $rates);
    }

    public function saveQuote(Request $request, array $snapshot, array $recipient, array $estimate, array $rate, array $rates, string $kind = 'merchandise', ?string $activeKey = null): array
    {
        $mode = app(CheckoutEnvironment::class)->mode();
        $id = (string) Str::uuid();
        $tax = ['amount_total' => $snapshot['subtotal'] + $estimate['shipping'], 'tax_amount_exclusive' => 0, 'id' => null];
        if ($mode === 'live') {
            $tax = app(CheckoutStripe::class)->call('tax.calculations', 'create', ['currency' => 'usd',
                'customer_details' => ['address_source' => $kind === 'digital' ? 'billing' : 'shipping', 'address' => $this->stripeAddress($recipient)],
                'shipping_cost' => ['amount' => $estimate['shipping'], 'tax_behavior' => 'exclusive', 'tax_code' => 'txcd_92010001'],
                'line_items' => array_map(fn ($item) => ['amount' => $item['unit_price'] * $item['quantity'], 'quantity' => $item['quantity'],
                    'reference' => $item['product_id'].':'.$item['sync_variant_id'], 'tax_behavior' => 'exclusive',
                    'tax_code' => config('shop-checkout.tax_codes.'.$item['category'], 'txcd_99999999')], $snapshot['items'])]);
        }
        if ((int) $tax['amount_total'] !== $snapshot['subtotal'] + $estimate['shipping'] + (int) $tax['tax_amount_exclusive']) throw new CheckoutException('The order total could not be confirmed. Please try again.');
        if ($tax['amount_total'] > 99999999 || ($tax['amount_total'] > 0 && $tax['amount_total'] < 50)
            || ($kind === 'merchandise' && $tax['amount_total'] === 0)) throw new CheckoutException('This order total is not supported. Please contact us.');
        $order = ShopOrder::create(['id' => $id, 'owner_hash' => $this->owner($request), 'user_id' => optional($request->user('web'))->id,
            'kind' => $kind, 'active_key' => $activeKey,
            'mode' => $mode, 'printful_store_id' => app(Storefront::class)->storeId(), 'bag_hash' => $snapshot['hash'],
            'items' => $snapshot['items'], 'recipient' => $recipient, 'currency' => 'usd', 'subtotal' => $snapshot['subtotal'],
            'shipping' => $estimate['shipping'], 'tax' => $tax['tax_amount_exclusive'], 'total' => $tax['amount_total'],
            'fulfillment_cost' => $estimate['total'], 'shipping_id' => $rate['id'], 'shipping_name' => $rate['name'],
            'stripe_tax_calculation' => $tax['id'], 'printful_external_id' => substr(hash('sha256', 'pianolit:'.$id), 0, 32),
            'expires_at' => now()->addMinutes((int) config('shop-checkout.quote_minutes', 15))]);
        return ['order_id' => $id, 'subtotal' => $order->subtotal, 'shipping' => $order->shipping, 'tax' => $order->tax,
            'expires_at' => $order->expires_at->toIso8601String(),
            'discount' => array_sum(array_map(fn ($item) => ($item['discount'] ?? 0) * $item['quantity'], $snapshot['items'])),
            'total' => $order->total, 'currency' => 'usd', 'shipping_id' => $rate['id'],
            'rates' => array_map(fn ($item) => ['id' => $item['id'], 'name' => $item['name'], 'amount' => app(PrintfulOrders::class)->cents($item['rate'])], $rates)];
    }

    public function stripeAddress(array $recipient): array
    {
        return ['line1' => $recipient['address1'], 'line2' => $recipient['address2'] ?? '', 'city' => $recipient['city'],
            'state' => $recipient['state_code'] ?? '', 'postal_code' => $recipient['zip'] ?? '', 'country' => $recipient['country_code']];
    }

    public function prepare(Request $request, string $id): array
    {
        $this->owned($request, $id);
        return DB::transaction(function () use ($request, $id) {
            $order = ShopOrder::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($order->paid_at) return ['paid' => true, 'redirect' => route('shop.order', $order->id)];
            $snapshot = $order->kind === 'digital' ? app(DigitalCheckout::class)->current($request, $order) : $this->snapshot($request);
            if ($order->expires_at->isPast() || $order->bag_hash !== $snapshot['hash']) throw new CheckoutException('Your order quote has changed. Please review the updated total before paying.', 409);
            if ($order->kind === 'digital' && $order->total === 0) {
                $order->update(['paid_at' => now(), 'status' => 'paid']);
                app(DigitalCheckout::class)->grant($order, null);
                return ['paid' => true, 'redirect' => route('shop.order', $order->id)];
            }
            if ($order->mode === 'demo') return ['demo' => true, 'order_id' => $order->id];
            $gateway = app(CheckoutStripe::class);
            $intent = $order->stripe_payment_intent ? $gateway->call('paymentIntents', 'retrieve', [], $order->stripe_payment_intent)
                : $gateway->call('paymentIntents', 'create', ['amount' => $order->total, 'currency' => $order->currency,
                    'payment_method_types' => ['card'], 'receipt_email' => $order->recipient['email'],
                    ...($order->kind === 'merchandise' ? ['shipping' => ['name' => $order->recipient['name'], 'address' => $this->stripeAddress($order->recipient)]] : []),
                    'metadata' => ['pianolit_shop_order' => $order->id]], null, $order->id.':payment');
            $this->verifyIntent($order, $intent, false);
            $order->update(['stripe_payment_intent' => $intent['id'], 'status' => 'payment_pending']);
            return ['order_id' => $order->id, 'client_secret' => $intent['client_secret'], 'return_url' => route('shop.order', $order->id)];
        }, 3);
    }

    public function verifyIntent(ShopOrder $order, array $intent, bool $paid = true): void
    {
        if (($order->stripe_payment_intent && $order->stripe_payment_intent !== ($intent['id'] ?? null))
            || ($intent['metadata']['pianolit_shop_order'] ?? null) !== $order->id
            || (int) ($intent['amount'] ?? -1) !== (int) $order->total || ($intent['currency'] ?? null) !== $order->currency
            || !array_key_exists('livemode', $intent) || (bool) $intent['livemode'] !== ($order->mode === 'live')
            || ($paid && (($intent['status'] ?? null) !== 'succeeded' || (int) ($intent['amount_received'] ?? -1) !== (int) $order->total))) {
            throw new CheckoutException('Payment has not been confirmed for this order. Please check its status before trying again.');
        }
    }

    public function paid(string $id): ShopOrder
    {
        return DB::transaction(function () use ($id) {
            $order = ShopOrder::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($order->mode === 'demo') throw new CheckoutException('Demo orders cannot use Stripe payment confirmation.');
            if (!$order->stripe_payment_intent) throw new CheckoutException('Payment confirmation is still being prepared.');
            $intent = app(CheckoutStripe::class)->call('paymentIntents', 'retrieve', ['expand' => ['latest_charge']], $order->stripe_payment_intent);
            $this->verifyIntent($order, $intent);
            if ($order->status === 'review_required') return $order;
            if (!empty($intent['latest_charge']['amount_refunded']) || !empty($intent['latest_charge']['disputed'])) {
                $order->update(['status' => 'review_required', 'last_error' => 'payment_refunded_or_disputed']);
                return $order->fresh();
            }
            if (!$order->paid_at) $order->update(['paid_at' => now(), 'status' => 'paid']);
            if ($order->stripe_tax_calculation && !$order->stripe_tax_transaction) {
                $transaction = app(CheckoutStripe::class)->call('tax.transactions', 'createFromCalculation',
                    ['calculation' => $order->stripe_tax_calculation, 'reference' => $order->id], null, $order->id.':tax');
                $order->update(['stripe_tax_transaction' => $transaction['id']]);
            }
            if ($order->kind === 'digital') app(DigitalCheckout::class)->grant($order, is_array($intent['latest_charge'] ?? null) ? $intent['latest_charge']['id'] : ($intent['latest_charge'] ?? null));
            else app(OrderEmails::class)->confirmation($order);
            return $order->fresh();
        }, 3);
    }

    public function demo(Request $request, string $id): ShopOrder
    {
        $order = $this->owned($request, $id);
        if (!app()->environment('local', 'testing') || app(CheckoutEnvironment::class)->mode() !== 'demo' || $order->mode !== 'demo') abort(404);
        $this->prepare($request, $id);
        if (!$order->paid_at) $order->update(['paid_at' => now(), 'status' => 'test_complete']);
        if ($order->kind === 'digital') app(DigitalCheckout::class)->grant($order, null);
        else app(OrderEmails::class)->confirmation($order);
        return $order->fresh();
    }

    public function clearPurchased(Request $request, ShopOrder $order): void
    {
        $cleared = $request->session()->get('shop_cleared_orders', []);
        if ($order->kind !== 'merchandise' || !$order->paid_at || in_array($order->id, $cleared, true) || !hash_equals($order->owner_hash, $this->owner($request))) return;
        $saved = $request->session()->get(MerchandiseBag::SESSION_KEY, []);
        foreach ($order->items as $item) {
            $key = $item['product_id'].':'.$item['sync_variant_id'];
            if (!isset($saved[$key])) continue;
            $quantity = (int) $saved[$key]['quantity'] - $item['quantity'];
            if ($quantity > 0) $saved[$key]['quantity'] = $quantity;
            else unset($saved[$key]);
        }
        $request->session()->put(MerchandiseBag::SESSION_KEY, $saved);
        $request->session()->put('shop_cleared_orders', array_slice(array_merge($cleared, [$order->id]), -50));
    }
}
