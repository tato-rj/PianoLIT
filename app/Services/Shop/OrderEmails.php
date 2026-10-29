<?php

namespace App\Services\Shop;

use App\Mail\AutomaticEmailSettings;
use App\Mail\Shop\{MerchandiseOrderConfirmed, MerchandiseOrderShipped, MerchandiseOrderDelivered};
use App\Shop\{ShopOrder, ShopOrderEmail, ShopShipment};
use Illuminate\Support\Facades\{DB, Mail};

class OrderEmails
{
    const TYPES = [
        'confirmation' => ['label' => 'Purchase confirmation', 'setting' => 'merchandise-confirmation'],
        'shipping' => ['label' => 'Shipping notification', 'setting' => 'merchandise-shipped'],
        'delivery' => ['label' => 'Delivery confirmation', 'setting' => 'merchandise-delivered'],
    ];

    public function confirmation(ShopOrder $order): void
    {
        if (!$this->recordable($order)) return;
        ShopOrderEmail::firstOrCreate(['delivery_key' => $order->id.':confirmed'], ['order_id' => $order->id, 'kind' => 'confirmation',
            'status' => $order->mode === 'live' ? 'pending' : 'preview']);
    }

    public function shipping(ShopOrder $order, ShopShipment $shipment): void
    {
        if (!$this->recordable($order)) return;
        $this->confirmation($order);
        ShopOrderEmail::firstOrCreate(['delivery_key' => $order->id.':shipment:'.$shipment->id],
            ['order_id' => $order->id, 'shipment_id' => $shipment->id, 'kind' => 'shipping', 'status' => $order->mode === 'live' ? 'pending' : 'preview']);
    }

    public function delivered(ShopOrder $order): bool
    {
        if (!$this->recordable($order) || !app(OrderTracking::class)->fullyDelivered($order)) return false;
        $email = ShopOrderEmail::firstOrCreate(['delivery_key' => $order->id.':delivered'], ['order_id' => $order->id, 'kind' => 'delivery',
            'status' => $order->mode === 'live' ? 'pending' : 'preview']);
        return $email->wasRecentlyCreated;
    }

    public function message(ShopOrderEmail $email): \Illuminate\Mail\Mailable
    {
        if ($email->kind === 'confirmation') return new MerchandiseOrderConfirmed($email->order);
        if ($email->kind === 'delivery') return new MerchandiseOrderDelivered($email->order);
        if ($email->kind === 'shipping' && $email->shipment && $email->shipment->order_id === $email->order_id) {
            return new MerchandiseOrderShipped($email->order, $email->shipment);
        }
        throw new \LogicException('Unknown order email.');
    }

    private function recordable(ShopOrder $order): bool
    {
        return $order->kind === 'merchandise' && $order->paid_at && ($order->mode === 'live'
            || (app()->environment('local') && in_array($order->mode, ['demo', 'sandbox'], true) && (int) $order->printful_store_id === 0));
    }

    public function deliver(int $id): void
    {
        // Previewing/local checkout must never send customer mail, even with cached live settings.
        if (!app()->environment('production')) return;
        $email = DB::transaction(function () use ($id) {
            $email = ShopOrderEmail::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($email->status !== 'pending') return null;
            $order = $email->order;
            if (!$order || $order->mode !== 'live' || !$order->paid_at || $order->kind !== 'merchandise') return null;
            if ($email->kind === 'shipping' && $order->emails()->where('kind', 'confirmation')->whereIn('status', ['pending', 'sending'])->exists()) return null;
            if ($email->kind === 'delivery' && (!app(OrderTracking::class)->fullyDelivered($order)
                || $order->emails()->whereIn('kind', ['confirmation', 'shipping'])->whereIn('status', ['pending', 'sending'])->exists())) return null;
            $key = self::TYPES[$email->kind]['setting'] ?? null;
            if (!$key) { $email->update(['status' => 'uncertain', 'last_error' => 'unknown_email_type']); return null; }
            if (!app(AutomaticEmailSettings::class)->enabled($key)) { $email->update(['status' => 'suppressed']); return null; }
            $email->update(['status' => 'sending', 'claimed_at' => now()]);
            return $email;
        }, 3);
        if (!$email) return;
        try {
            Mail::to($email->order->recipient['email'])->send($this->message($email));
            $email->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
        } catch (\Throwable $exception) {
            // SMTP may have accepted an email before losing its response. Hold for review,
            // rather than automatically sending a duplicate purchase/shipping message.
            $email->update(['status' => 'uncertain', 'last_error' => class_basename($exception)]);
        }
    }

    public function deliverPending(): void
    {
        if (!app()->environment('production')) return;
        ShopOrderEmail::where('status', 'sending')->where('claimed_at', '<', now()->subMinutes(15))
            ->update(['status' => 'uncertain', 'last_error' => 'delivery_interrupted']);
        ShopOrderEmail::where('status', 'pending')->orderBy('id')->limit(50)->pluck('id')->each(fn ($id) => $this->deliver($id));
    }
}
