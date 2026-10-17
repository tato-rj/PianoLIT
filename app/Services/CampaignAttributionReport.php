<?php

namespace App\Services;

use App\Activity\ExcludedAccounts;
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignAttributionReport
{
    const SORTS = [
        'arrivals_desc' => 'Most arrivals', 'arrivals_asc' => 'Fewest arrivals',
        'source_asc' => 'Source · A–Z', 'source_desc' => 'Source · Z–A',
        'trials_desc' => 'Most trials', 'paid_desc' => 'Most first payments',
        'latest_desc' => 'Latest arrival',
    ];
    const PERIODS = ['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days', 'all' => 'All retained arrivals'];

    public function admin(Request $request)
    {
        $filters = AdminTableFilters::fromRequest($request, self::SORTS, 'arrivals_desc', [
            'period' => 'nullable|string|in:7,30,90,all',
        ]);
        $filters['period'] = $filters['period'] ?? '30';
        $since = $filters['period'] === 'all' ? null : now()->subDays((int) $filters['period'] - 1)->startOfDay();
        $query = $this->grouped($since, $filters['search']);
        $order = [
            'arrivals_desc' => ['arrivals', 'desc'], 'arrivals_asc' => ['arrivals', 'asc'],
            'source_asc' => ['v.utm_source', 'asc'], 'source_desc' => ['v.utm_source', 'desc'],
            'trials_desc' => ['trials', 'desc'], 'paid_desc' => ['first_paid_subscriptions', 'desc'],
            'latest_desc' => ['last_arrived_at', 'desc'],
        ][$filters['sort']];
        $rows = $query->orderBy(...$order);
        foreach (CampaignParameters::FIELDS as $field) $rows->orderBy('v.'.$field);
        $rows = $rows->paginate($filters['per_page'])->appends(AdminTableFilters::query($filters, 'arrivals_desc'));
        $this->withRevenue($rows->getCollection(), $since, $filters['search']);
        return ['campaigns' => $rows, 'filters' => $filters, 'enabled' => (bool) config('attribution.enabled')];
    }

    public function rows($since)
    {
        $rows = $this->grouped($since)->orderByDesc('arrivals')->orderBy('v.utm_source')->get();
        foreach ($rows->chunk(96) as $chunk) $this->withRevenue($chunk, $since);
        return $rows->map(function ($row) {
            return [
                'campaign' => $row->utm_campaign !== '' ? $row->utm_campaign : '(source only)', 'source' => $row->utm_source,
                'medium' => $row->utm_medium, 'content' => $row->utm_content,
                'arrivals' => (int) $row->arrivals, 'trials' => (int) $row->trials,
                'first_paid_subscriptions' => (int) $row->first_paid_subscriptions,
                'first_payment_revenue' => $row->first_payment_revenue,
            ];
        })->all();
    }

    private function visits($since, $search = '')
    {
        $query = ExcludedAccounts::owned(DB::table('campaign_visits as v'), 'v.user_id')->where('v.utm_source', '<>', '');
        if ($since) $query->where('v.created_at', '>=', $since);
        if ($search !== '') {
            // A portable literal substring match (including underscores in UTMs).
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], strtolower($search)).'%';
            $query->where(function ($query) use ($pattern) {
                foreach (CampaignParameters::FIELDS as $field) $query->orWhereRaw("v.$field LIKE ? ESCAPE '!'", [$pattern]);
            });
        }
        return $query;
    }

    private function firstPayments()
    {
        return DB::table('stripe_payment_receipts as p')->join('campaign_subscriptions as s', function ($join) {
            $join->on('p.stripe_subscription_id', '=', 's.stripe_subscription_id')->on('p.stripe_customer_id', '=', 's.stripe_customer_id');
        })->where('p.kind', 'invoice')->whereNotExists(function ($query) {
            // The earliest paid time wins, regardless of delivery order. Renewals
            // and another customer's subscription never add conversions.
            $query->selectRaw('1')->from('stripe_payment_receipts as earlier')->where('earlier.kind', 'invoice')
                ->whereColumn('earlier.stripe_subscription_id', 'p.stripe_subscription_id')
                ->whereColumn('earlier.stripe_customer_id', 'p.stripe_customer_id')
                ->where(function ($query) {
                    $query->whereColumn('earlier.paid_at', '<', 'p.paid_at')->orWhere(function ($query) {
                        $query->whereColumn('earlier.paid_at', 'p.paid_at')->whereColumn('earlier.stripe_object_id', '<', 'p.stripe_object_id');
                    });
                });
        });
    }

    private function grouped($since, $search = '')
    {
        $subscriptions = DB::table('campaign_subscriptions')->select('visit_id')->selectRaw('COUNT(trial_started_at) as trials')->groupBy('visit_id');
        $payments = $this->firstPayments()->select('s.visit_id')->selectRaw('COUNT(*) as paid')->groupBy('s.visit_id');
        return $this->visits($since, $search)
            ->leftJoinSub($subscriptions, 'subscriptions', 'subscriptions.visit_id', '=', 'v.id')
            ->leftJoinSub($payments, 'payments', 'payments.visit_id', '=', 'v.id')
            ->select(array_map(function ($field) { return 'v.'.$field; }, CampaignParameters::FIELDS))
            ->selectRaw('COUNT(*) as arrivals, SUM(COALESCE(subscriptions.trials, 0)) as trials, SUM(COALESCE(payments.paid, 0)) as first_paid_subscriptions, MAX(v.created_at) as last_arrived_at')
            ->groupBy(array_map(function ($field) { return 'v.'.$field; }, CampaignParameters::FIELDS));
    }

    private function withRevenue($rows, $since, $search = '')
    {
        if ($rows->isEmpty()) return;
        $revenue = $this->visits($since, $search)->joinSub(
            $this->firstPayments()->select('s.visit_id', 'p.currency', 'p.amount'), 'revenue', 'revenue.visit_id', '=', 'v.id'
        )->where(function ($query) use ($rows) {
            foreach ($rows as $row) {
                $query->orWhere(function ($query) use ($row) {
                    foreach (CampaignParameters::FIELDS as $field) $query->where('v.'.$field, $row->$field);
                });
            }
        })->select(array_map(function ($field) { return 'v.'.$field; }, CampaignParameters::FIELDS))
            ->addSelect('revenue.currency')->selectRaw('SUM(revenue.amount) as amount')
            ->groupBy(array_merge(array_map(function ($field) { return 'v.'.$field; }, CampaignParameters::FIELDS), ['revenue.currency']))
            ->orderBy('revenue.currency')->get()->groupBy(function ($row) { return CampaignParameters::key((array) $row); });
        foreach ($rows as $row) {
            $row->first_payment_revenue = $revenue->get(CampaignParameters::key((array) $row), collect())->map(function ($amount) {
                return strtoupper($amount->currency).' '.(in_array($amount->currency, ['usd', 'chf', 'eur', 'gbp'], true)
                    ? number_format($amount->amount / 100, 2, '.', '') : $amount->amount.' minor units');
            })->implode(', ');
        }
    }
}
