<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Shop\{CheckoutEnvironment, CheckoutException, DigitalCheckout, MerchandiseBag, ShopCheckout, MerchandiseFulfillment};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function show(Request $request, ShopCheckout $checkout, CheckoutEnvironment $environment)
    {
        try {
            if (!$environment->available()) throw new CheckoutException('Checkout is temporarily unavailable. Please try again later.');
            $mode = $environment->mode();
            $snapshot = $checkout->snapshot($request);
            $countries = $checkout->countries();
            $stripeKey = $mode === 'demo' ? '' : $environment->credentials()[0];
            $data = compact('mode', 'snapshot', 'countries', 'stripeKey') + ['kind' => 'merchandise', 'cancelUrl' => route('shop.bag')];
            if ($request->expectsJson()) return response()->json(['html' => view('shop.storefront.checkout.modal', $data)->render()])->header('Cache-Control', 'private, no-store');
            return response()->view('shop.storefront.checkout.index', $data)->header('Cache-Control', 'private, no-store');
        } catch (\Throwable $exception) {
            if ($request->expectsJson()) return $this->failure($exception);
            return redirect()->route('shop.bag')->withErrors(['checkout' => $exception instanceof CheckoutException ? $exception->getMessage() : 'Checkout could not open. Please try again.'], 'storefront');
        }
    }

    public function digital(Request $request, $product, string $type)
    {
        $user = $request->user('web');
        abort_unless($user, 401);
        $purchase = $user->purchasesOf($product)->first();
        if ($purchase || $product->isFree() || $user->isEligibleForFreeMonthlyProduct() || !app(CheckoutEnvironment::class)->available('digital')) {
            if ($request->expectsJson()) {
                if ($purchase || $product->isFree() || $user->isEligibleForFreeMonthlyProduct()) return response()->json(['redirect' => $purchase ? route('shop.success', $purchase->id) : $product->showRoute()]);
                return response()->json(['message' => 'Secure checkout is temporarily unavailable. Please try again later.'], 503);
            }
            return view('shop.checkout.index', compact('product'));
        }
        try {
            $snapshot = app(DigitalCheckout::class)->snapshot($request, $type, $product->id);
            $mode = app(CheckoutEnvironment::class)->mode();
            $countries = app(ShopCheckout::class)->countries('digital');
            $stripeKey = $mode === 'demo' ? '' : app(CheckoutEnvironment::class)->credentials()[0];
            $data = compact('snapshot', 'mode', 'countries', 'stripeKey') + ['kind' => 'digital', 'productType' => $type, 'productId' => $product->id, 'cancelUrl' => $product->showRoute()];
            if ($request->expectsJson()) return response()->json(['html' => view('shop.storefront.checkout.modal', $data)->render()])->header('Cache-Control', 'private, no-store');
            return response()->view('shop.storefront.checkout.index', $data)->header('Cache-Control', 'private, no-store');
        } catch (\Throwable $exception) { return $this->failure($exception); }
    }

    public function quote(Request $request, ShopCheckout $checkout)
    {
        try { return response()->json($checkout->quote($request))->header('Cache-Control', 'private, no-store'); }
        catch (\Throwable $exception) { return $this->failure($exception); }
    }

    public function prepare(Request $request, ShopCheckout $checkout)
    {
        $data = $request->validate(['order_id' => 'required|uuid']);
        try { return response()->json($checkout->prepare($request, $data['order_id'])); }
        catch (\Throwable $exception) { return $this->failure($exception); }
    }

    public function complete(Request $request, ShopCheckout $checkout)
    {
        $data = $request->validate(['order_id' => 'required|uuid']);
        $order = $checkout->owned($request, $data['order_id']);
        try {
            $order = $order->mode === 'demo' ? $checkout->demo($request, $order->id) : $checkout->paid($order->id);
            app(MerchandiseFulfillment::class)->process($order->id);
            $checkout->clearPurchased($request, $order);
            return response()->json(['redirect' => route('shop.order', $order->id)]);
        } catch (\Throwable $exception) { return $this->failure($exception); }
    }

    public function order(Request $request, string $order, ShopCheckout $checkout)
    {
        $order = $checkout->owned($request, $order);
        if ($request->query->has('payment_intent_client_secret') || $request->query->has('payment_intent')) {
            return redirect()->route('shop.order', $order->id)->header('Referrer-Policy', 'no-referrer')->header('Cache-Control', 'private, no-store');
        }
        if (!$order->paid_at && $order->stripe_payment_intent) {
            try { $order = $checkout->paid($order->id); app(MerchandiseFulfillment::class)->process($order->id); }
            catch (\Throwable $exception) { /* Keep the pending receipt; never resubmit payment here. */ }
        }
        $checkout->clearPurchased($request, $order);
        return response()->view('shop.storefront.order', ['order' => $order->fresh(), 'shoppingBag' => app(MerchandiseBag::class)->contents($request)])
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    private function failure(\Throwable $exception)
    {
        if ($exception instanceof ValidationException) throw $exception;
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) throw $exception;
        return response()->json(['refresh_quote' => $exception instanceof CheckoutException && $exception->getCode() === 409,
            'message' => $exception instanceof CheckoutException ? $exception->getMessage()
            : 'We could not confirm checkout. Please try again. You can check your order before making another payment.'], 422);
    }
}
