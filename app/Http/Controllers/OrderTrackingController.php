<?php

namespace App\Http\Controllers;

use App\Services\Shop\OrderTracking;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function index(Request $request, OrderTracking $tracking)
    {
        $orders = $tracking->visible()->where('user_id', $request->user('web')->id)->with('shipments')->latest('paid_at')->paginate(8);
        return response()->view('users.orders.index', compact('orders', 'tracking'))->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function show(Request $request, string $order, OrderTracking $tracking)
    {
        $order = $tracking->visible()->with('shipments')->findOrFail($order);
        return response()->view('shop.storefront.order', compact('order') + ['privateTracking' => true,
            'shoppingBag' => app(\App\Services\Shop\MerchandiseBag::class)->contents($request)])
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function samplePackage(Request $request, string $order, string $shipment, OrderTracking $tracking)
    {
        abort_unless(app()->environment('local'), 404);
        $order = $tracking->visible()->whereIn('mode', ['demo', 'sandbox'])->where('printful_store_id', 0)->findOrFail($order);
        $package = $order->shipments()->where('printful_id', $shipment)->firstOrFail();
        $order->setRelation('shipments', collect([$package]));
        return response()->view('shop.storefront.order', compact('order') + ['privateTracking' => true,
            'shoppingBag' => app(\App\Services\Shop\MerchandiseBag::class)->contents($request)])
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
