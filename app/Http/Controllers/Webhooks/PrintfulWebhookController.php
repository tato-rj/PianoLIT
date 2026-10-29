<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Shop\{PrintfulWebhooks, ShipmentUpdates};
use App\Shop\ShopOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintfulWebhookController extends Controller
{
    public function __invoke(Request $request, PrintfulWebhooks $webhooks, ShipmentUpdates $updates)
    {
        $webhooks->verify($request);
        $event = json_decode($request->getContent(), true);
        abort_unless(is_array($event) && is_scalar($event['store_id'] ?? null)
            && (string) $event['store_id'] === app(\App\Services\Shop\Printful::class)->storeId(), 400);
        if (!in_array($event['type'] ?? '', PrintfulWebhooks::EVENTS, true)) return response('Received', 200);
        $validator = \Illuminate\Support\Facades\Validator::make($event, ['occurred_at' => 'required|date',
            'data.order' => 'required|array', 'data.shipment' => 'required|array', 'data.order.id' => 'required|integer|min:1',
            'data.order.external_id' => 'required|string|size:32', 'data.shipment.id' => 'required|integer|min:1']);
        abort_if($validator->fails(), 400);
        $order = ShopOrder::where('mode', 'live')->where('kind', 'merchandise')->whereNotNull('paid_at')
            ->where('printful_store_id', $event['store_id'])->where('printful_external_id', $event['data']['order']['external_id'])->first();
        if (!$order) return response('Received', 200);
        abort_if($order->printful_order_id && $order->printful_order_id !== (string) $event['data']['order']['id'], 400);
        $id = hash('sha256', json_encode([$event['store_id'], $event['type'], $event['data']['order']['id'], $event['data']['shipment']['id'], $event['occurred_at']]));
        if (DB::table('printful_webhook_receipts')->where('id', $id)->exists()) return response('Received', 200);
        try { $updates->refresh($order, $event, $id); }
        catch (\Throwable $exception) {
            $order->update(['tracking_error' => class_basename($exception)]);
            // Retry provider lag/failures; never acknowledge missing shipment details as complete.
            return response('Shipment information is not ready. Please retry.', 503);
        }
        return response('Received', 200);
    }
}
