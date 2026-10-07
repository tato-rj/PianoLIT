<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneCampaignAttribution extends Command
{
    protected $signature = 'attribution:prune';
    protected $description = 'Remove expired campaign links and aged unmatched invoice receipts';

    public function handle()
    {
        if (! config('attribution.enabled')) return 0;
        DB::transaction(function () {
            DB::table('campaign_visits')->where('created_at', '<', now()->subDays(config('attribution.retention_days')))->delete();
            DB::table('stripe_payment_receipts')->where('kind', 'invoice')->where('created_at', '<', now()->subDays(config('attribution.retention_days')))
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))->from('campaign_subscriptions')
                        ->whereColumn('campaign_subscriptions.stripe_subscription_id', 'stripe_payment_receipts.stripe_subscription_id')
                        ->whereColumn('campaign_subscriptions.stripe_customer_id', 'stripe_payment_receipts.stripe_customer_id');
                })->delete();
        });
        $this->info('Expired campaign links pruned; billing payments and charge retry markers preserved.');
        return 0;
    }
}
