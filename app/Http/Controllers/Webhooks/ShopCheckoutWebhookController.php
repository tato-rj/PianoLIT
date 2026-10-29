<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Shop\ShopOrder;
use App\Services\Shop\{CheckoutEnvironment, ShopCheckout, MerchandiseFulfillment};
use Illuminate\Http\Request;

class ShopCheckoutWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $secret = config('shop-checkout.stripe_webhook_secret');
        abort_unless(is_string($secret) && $secret !== '' && app(CheckoutEnvironment::class)->mode() !== 'demo', 503);
        try { $event = \Stripe\Webhook::constructEvent($request->getContent(), $request->header('Stripe-Signature', ''), $secret, 300)->toArray(); }
        catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $exception) { abort(400, 'Invalid webhook signature.'); }
        if (($event['type'] ?? null) !== 'payment_intent.succeeded') return response('Received', 200);
        $intent = $event['data']['object'] ?? [];
        $order = ShopOrder::find($intent['metadata']['pianolit_shop_order'] ?? '');
        if (!$order) return response('Received', 200);
        abort_unless((bool) ($event['livemode'] ?? null) === ($order->mode === 'live'), 400);
        abort_unless($order->stripe_payment_intent && $order->stripe_payment_intent === ($intent['id'] ?? null), 409);
        app(ShopCheckout::class)->paid($order->id);
        app(MerchandiseFulfillment::class)->process($order->id);
        return response('Received', 200);
    }
}
