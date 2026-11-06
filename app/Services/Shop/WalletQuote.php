<?php

namespace App\Services\Shop;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Price the limited address supplied before a wallet releases full contact details. */
class WalletQuote
{
    public function preview(Request $request): array
    {
        if (!app(CheckoutEnvironment::class)->available()) throw new CheckoutException('Merchandise checkout is temporarily unavailable.');
        $data = $request->validate(['country_code' => 'required|string|size:2', 'state_code' => 'nullable|string|max:32',
            'city' => 'nullable|string|max:100', 'zip' => 'nullable|string|max:20',
            'email' => 'nullable|email:rfc|max:254', 'shipping_id' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:100', 'address1' => 'nullable|string|max:150', 'address2' => 'nullable|string|max:150']);
        $recipient = array_map(fn ($value) => trim((string) $value), array_intersect_key($data, array_flip(['country_code', 'state_code', 'city', 'zip', 'name', 'address1', 'address2'])));
        $recipient['country_code'] = strtoupper($recipient['country_code']);
        $checkout = app(ShopCheckout::class);
        $country = collect($checkout->countries())->firstWhere('code', $recipient['country_code']);
        if (!$country) throw ValidationException::withMessages(['country_code' => 'Please choose an available delivery country.']);
        if (!empty($country['states']) && !in_array($recipient['state_code'] ?? '', array_column($country['states'], 'code'), true)) {
            throw ValidationException::withMessages(['state_code' => 'Please choose a state or province for this country.']);
        }
        $snapshot = $checkout->snapshot($request);
        $email = $data['email'] ?? optional($request->user('web'))->email ?? $request->session()->get('shop_newsletter_email');
        if ($email) $snapshot = app(FirstOrderDiscount::class)->apply($request, $snapshot, $email);
        $provider = app(PrintfulOrders::class);
        $rates = array_values(array_filter($provider->rates($recipient, $snapshot['items'], $snapshot['currency']),
            fn ($rate) => strtoupper($rate['currency'] ?? '') === $snapshot['currency'] && !empty($rate['id'])));
        if (!$rates) throw new CheckoutException('These items cannot currently be delivered to this address. Please try another address.');
        $selected = $data['shipping_id'] ?? null;
        $rate = $selected ? collect($rates)->firstWhere('id', $selected) : $rates[0];
        if (!$rate) throw new CheckoutException('Please choose an available delivery option.');
        $shipping = $provider->cents($rate['rate']);
        // A prior wallet approval or the normal form can supply the full address.
        // Reuse the exact estimate when available, without fabricating private fields.
        if (!empty($recipient['name']) && !empty($recipient['address1']) && !empty($recipient['city'])) {
            $shipping = $provider->estimate($recipient, $snapshot['items'], $snapshot['currency'], $rate['id'])['shipping'];
        }
        $tax = $checkout->totals($snapshot, $recipient, $shipping);
        // No order, reservation, PaymentIntent or provider order is created here.
        return ['subtotal' => $snapshot['subtotal'], 'shipping' => $shipping, 'tax' => (int) $tax['tax_amount_exclusive'],
            'total' => (int) $tax['amount_total'], 'currency' => 'usd', 'shipping_id' => $rate['id'],
            'discount' => app(FirstOrderDiscount::class)->amount($snapshot['items']),
            'rates' => array_map(fn ($item) => ['id' => $item['id'], 'name' => $item['name'], 'amount' => $provider->cents($item['rate'])], $rates)];
    }
}
