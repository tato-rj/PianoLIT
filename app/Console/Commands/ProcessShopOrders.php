<?php

namespace App\Console\Commands;

use App\Shop\ShopOrder;
use App\Services\Shop\{CheckoutEnvironment, ShopCheckout, MerchandiseFulfillment};
use Illuminate\Console\Command;

class ProcessShopOrders extends Command
{
    protected $signature = 'shop:process-orders';
    protected $description = 'Reconcile shop payments, digital delivery and paid merchandise fulfillment';

    public function handle()
    {
        try { $mode = app(CheckoutEnvironment::class)->mode(); }
        catch (\Throwable $exception) { $this->warn('Shop checkout is disabled.'); return 0; }
        if ($mode === 'demo') { $this->info('Demo mode: no provider requests.'); return 0; }
        ShopOrder::where('mode', $mode)->whereIn('status', ['payment_pending', 'paid'])
            ->where(function ($query) { $query->whereNotNull('paid_at')->orWhere('created_at', '>', now()->subDays(7)); })
            ->orderBy('updated_at')->limit(100)->get()->each(function ($order) {
                try {
                    app(ShopCheckout::class)->paid($order->id);
                    app(MerchandiseFulfillment::class)->process($order->id);
                } catch (\Throwable $exception) {
                    // A pending payment is never retried as a new payment.
                }
                $order->touch();
            });
        $this->info('Shop order reconciliation complete.');
        return 0;
    }
}
