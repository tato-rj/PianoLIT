<?php

namespace App\Services\Shop;

use App\{User, Merchandise\Purchase, Shop\ShopOrder};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FirstOrderDiscount
{
    public function emailHash(string $email): string
    {
        return hash_hmac('sha256', mb_strtolower(trim($email)), config('app.key'));
    }

    public function grant(string $email): void
    {
        // A repeat signup never resets a reservation or an already redeemed offer.
        DB::table('shop_first_order_discounts')->insertOrIgnore([
            'email_hash' => $this->emailHash($email), 'mode' => app()->environment('production') ? 'live' : app(CheckoutEnvironment::class)->mode(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function history(string $email, ?int $userId, string $mode): bool
    {
        $accounts = User::whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($email))])->pluck('id')->all();
        if ($userId) $accounts[] = $userId;
        if (ShopOrder::where('mode', $mode)->whereNotNull('paid_at')->where('total', '>', 0)
            ->where(function ($query) use ($email, $accounts) {
                $query->where('checkout_email_hash', $this->emailHash($email));
                if ($accounts) $query->orWhereIn('user_id', $accounts);
            })->exists()) return true;
        // Free downloads do not use the offer. Sandbox/demo digital purchases must
        // not make a customer ineligible for their first real purchase.
        return $accounts && Purchase::whereIn('user_id', $accounts)->where('cost', '>', 0)
            ->whereNotIn('id', ShopOrder::select('purchase_id')->whereNotNull('purchase_id')->where('mode', '<>', $mode))->exists();
    }

    public function apply(Request $request, array $snapshot, string $email, ?ShopOrder $order = null): array
    {
        if ($snapshot['subtotal'] <= 0 || collect($snapshot['items'])->contains(fn ($item) => !empty($item['coupon']))) return $snapshot;
        $mode = app(CheckoutEnvironment::class)->mode();
        $offer = DB::table('shop_first_order_discounts')->where('email_hash', $this->emailHash($email))->where('mode', $mode)->first();
        if (!$offer || $offer->redeemed_order_id) return $snapshot;
        $reservedHere = $order && $offer->reserved_order_id === $order->id;
        if (!$reservedHere && $this->history($email, optional($request->user('web'))->id, $mode)) return $snapshot;
        $total = $snapshot['subtotal'];
        $discount = intdiv($total + 5, 10);
        if (!$discount) return $snapshot;
        $running = $allocated = 0;
        foreach ($snapshot['items'] as &$item) {
            $line = $item['unit_price'] * $item['quantity'];
            $running += $line;
            $share = intdiv($running * $discount, $total) - $allocated;
            $allocated += $share;
            $item['first_order_discount'] = $share;
            $item['line_total'] = $line - $share;
        }
        unset($item);
        $snapshot['first_order_discount_id'] = $offer->id;
        $snapshot['subtotal'] -= $discount;
        $snapshot['hash'] = hash('sha256', json_encode([$snapshot['items'], $offer->id]));
        return $snapshot;
    }

    public function amount(array $items): int
    {
        return array_sum(array_map(fn ($item) => ($item['discount'] ?? 0) * $item['quantity'] + ($item['first_order_discount'] ?? 0), $items));
    }

    public function label(array $items): string
    {
        return collect($items)->contains(fn ($item) => !empty($item['first_order_discount'])) ? 'First-order discount (10%)' : 'Coupon discount';
    }

    public function reserve(Request $request, ShopOrder $order): void
    {
        if (!$order->first_order_discount_id || $order->paid_at) return;
        // This transaction MUST commit before Stripe creation. A lost provider
        // response must not roll back the one-use reservation and enable another order.
        DB::transaction(function () use ($request, $order) {
            $userId = optional($request->user('web'))->id;
            if ($userId) User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $offer = DB::table('shop_first_order_discounts')->where('id', $order->first_order_discount_id)->lockForUpdate()->first();
            if (!$offer || $offer->mode !== $order->mode || $offer->email_hash !== $this->emailHash($order->recipient['email']) || $offer->redeemed_order_id) {
                throw new CheckoutException('Your welcome discount has changed. Please review your updated total.', 409);
            }
            if ($offer->reserved_order_id === $order->id) return;
            if ($this->history($order->recipient['email'], $userId, $order->mode)) {
                throw new CheckoutException('The welcome discount is for first orders. Please review your updated total.', 409);
            }
            // The account lock also prevents two different email offers being
            // reserved simultaneously by the same authenticated customer.
            if ($userId) {
                $others = DB::table('shop_first_order_discounts')->where('user_id', $userId)->where('mode', $order->mode)
                    ->where('id', '<>', $offer->id)->whereNotNull('reserved_order_id')->lockForUpdate()->get();
                foreach ($others as $other) {
                    if ($other->redeemed_order_id) throw new CheckoutException('The welcome discount is for first orders. Please review your updated total.', 409);
                    $this->release($other->reserved_order_id, $request);
                    DB::table('shop_first_order_discounts')->where('id', $other->id)->update(['reserved_order_id' => null, 'updated_at' => now()]);
                }
            }
            if ($offer->reserved_order_id) $this->release($offer->reserved_order_id, $request);
            DB::table('shop_first_order_discounts')->where('id', $offer->id)->update([
                'reserved_order_id' => $order->id, 'user_id' => $userId, 'updated_at' => now(),
            ]);
        }, 3);
    }

    private function release(string $id, Request $request): void
    {
        $previous = ShopOrder::findOrFail($id);
        $expired = $previous->expires_at->isPast() || $previous->status === 'abandoned';
        $sameBrowser = hash_equals($previous->owner_hash, app(ShopCheckout::class)->owner($request));
        if ($previous->paid_at || (!$expired && !$sameBrowser)) {
            throw new CheckoutException('Your welcome discount is already being used in another checkout. Please finish that order first.');
        }
        if ($previous->mode !== 'demo') {
            // Recover a lost create response with the original idempotency key.
            // Never release until the old payment is confirmed canceled.
            $checkout = app(ShopCheckout::class);
            $intent = $checkout->paymentIntent($previous);
            $checkout->verifyIntent($previous, $intent, false);
            if (!$expired && $intent['status'] === 'requires_action') {
                throw new CheckoutException('Your earlier payment is awaiting confirmation. Please finish that checkout first.');
            }
            if ($intent['status'] !== 'canceled') {
                if (!in_array($intent['status'], ['requires_payment_method', 'requires_confirmation', 'requires_action'], true)) {
                    throw new CheckoutException('Your earlier payment is still being checked. Please check that order before trying again.');
                }
                $intent = app(CheckoutStripe::class)->call('paymentIntents', 'cancel', [], $intent['id'], $previous->id.':cancel');
                if ($intent['status'] !== 'canceled') throw new CheckoutException('Your earlier payment is still being checked.');
            }
            $previous->stripe_payment_intent = $intent['id'];
        }
        $previous->status = 'abandoned';
        $previous->save();
    }

    public function redeem(ShopOrder $order): void
    {
        if (!$order->first_order_discount_id || !$order->paid_at) return;
        DB::table('shop_first_order_discounts')->where('id', $order->first_order_discount_id)
            ->where('reserved_order_id', $order->id)->whereNull('redeemed_order_id')
            ->update(['redeemed_order_id' => $order->id, 'updated_at' => now()]);
    }
}
