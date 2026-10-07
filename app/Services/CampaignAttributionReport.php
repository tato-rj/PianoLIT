<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CampaignAttributionReport
{
    public function rows($since)
    {
        $rows = [];
        foreach (config('attribution.campaigns', []) as $key => $campaign) {
            $visits = DB::table('campaign_visits')->where('campaign', $key)->where('created_at', '>=', $since);
            $subscriptions = DB::table('campaign_subscriptions as s')->join('campaign_visits as v', 'v.id', '=', 's.visit_id')
                ->where('v.campaign', $key)->where('v.created_at', '>=', $since);
            $firstPayments = DB::table('stripe_payment_receipts as p')->join('campaign_subscriptions as s', function ($join) {
                $join->on('p.stripe_subscription_id', '=', 's.stripe_subscription_id')->on('p.stripe_customer_id', '=', 's.stripe_customer_id');
            })->join('campaign_visits as v', 'v.id', '=', 's.visit_id')
                ->where('v.campaign', $key)->where('v.created_at', '>=', $since)->where('p.kind', 'invoice')
                // Earlier paid time wins regardless of webhook delivery order;
                // invoice identity breaks ties, so renewals never add conversions.
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))->from('stripe_payment_receipts as earlier')
                        ->where('earlier.kind', 'invoice')
                        ->whereColumn('earlier.stripe_subscription_id', 'p.stripe_subscription_id')
                        ->whereColumn('earlier.stripe_customer_id', 'p.stripe_customer_id')
                        ->where(function ($query) {
                            $query->whereColumn('earlier.paid_at', '<', 'p.paid_at')
                                ->orWhere(function ($query) {
                                    $query->whereColumn('earlier.paid_at', 'p.paid_at')->whereColumn('earlier.stripe_object_id', '<', 'p.stripe_object_id');
                                });
                        });
                });
            $revenue = (clone $firstPayments)->select('p.currency', DB::raw('SUM(p.amount) as amount'))->groupBy('p.currency')->get();
            $rows[] = [
                'campaign' => $key, 'source' => $campaign['utm_source'], 'content' => $campaign['utm_content'],
                'arrivals' => $visits->count(), 'trials' => (clone $subscriptions)->whereNotNull('s.trial_started_at')->count(),
                'first_paid_subscriptions' => (clone $firstPayments)->count(),
                'first_payment_revenue' => $revenue->map(function ($row) { return strtoupper($row->currency).' '.(in_array($row->currency, ['usd', 'chf', 'eur', 'gbp'], true) ? number_format($row->amount / 100, 2, '.', '') : $row->amount.' minor units'); })->implode(', '),
            ];
        }
        return $rows;
    }
}
