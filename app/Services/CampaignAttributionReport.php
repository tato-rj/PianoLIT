<?php

namespace App\Services;

use App\Activity\ExcludedAccounts;
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignAttributionReport
{
    const ADMIN_GROUPS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'landing_url'];
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
        $query = $this->grouped($since, $filters['search'], self::ADMIN_GROUPS);
        $order = [
            'arrivals_desc' => ['arrivals', 'desc'], 'arrivals_asc' => ['arrivals', 'asc'],
            'source_asc' => ['v.utm_source', 'asc'], 'source_desc' => ['v.utm_source', 'desc'],
            'trials_desc' => ['trials', 'desc'], 'paid_desc' => ['first_paid_subscriptions', 'desc'],
            'latest_desc' => ['last_arrived_at', 'desc'],
        ][$filters['sort']];
        $rows = $query->orderBy(...$order);
        foreach (self::ADMIN_GROUPS as $field) $rows->orderBy('v.'.$field);
        $rows = $rows->paginate($filters['per_page'])->appends(AdminTableFilters::query($filters, 'arrivals_desc'));
        $this->withRevenue($rows->getCollection(), $since, $filters['search'], self::ADMIN_GROUPS);
        $this->withLandingPages($rows->getCollection());
        return ['campaigns' => $rows, 'filters' => $filters, 'enabled' => (bool) config('attribution.enabled')];
    }

    public function rows($since)
    {
        $rows = $this->grouped($since)->orderByDesc('arrivals')->orderBy('v.utm_source')->get();
        foreach ($rows->chunk(96) as $chunk) $this->withRevenue($chunk, $since);
        return $rows->map(function ($row) {
            return [
                'campaign' => $row->utm_campaign !== '' ? $row->utm_campaign : '(not specified)', 'source' => $row->utm_source,
                'medium' => $row->utm_medium, 'content' => $row->utm_content,
                'arrivals' => (int) $row->arrivals, 'trials' => (int) $row->trials,
                'first_paid_subscriptions' => (int) $row->first_paid_subscriptions,
                'first_payment_revenue' => $row->first_payment_revenue,
            ];
        })->all();
    }

    private function visits($since, $search = '', $fields = CampaignParameters::FIELDS)
    {
        $query = ExcludedAccounts::owned(DB::table('campaign_visits as v'), 'v.user_id')->where(function ($query) {
            $query->where('v.utm_source', '<>', '')->orWhere('v.utm_medium', '<>', '')->orWhere('v.utm_campaign', '<>', '');
        });
        if ($since) $query->where('v.created_at', '>=', $since);
        if ($search !== '') {
            // A portable literal substring match (including underscores in UTMs).
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], strtolower($search)).'%';
            $query->where(function ($query) use ($pattern, $fields) {
                foreach ($fields as $field) $query->orWhereRaw("v.$field LIKE ? ESCAPE '!'", [$pattern]);
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

    private function grouped($since, $search = '', $fields = CampaignParameters::FIELDS)
    {
        $subscriptions = DB::table('campaign_subscriptions')->select('visit_id')->selectRaw('COUNT(trial_started_at) as trials')->groupBy('visit_id');
        $payments = $this->firstPayments()->select('s.visit_id')->selectRaw('COUNT(*) as paid')->groupBy('s.visit_id');
        return $this->visits($since, $search, $fields)
            ->leftJoinSub($subscriptions, 'subscriptions', 'subscriptions.visit_id', '=', 'v.id')
            ->leftJoinSub($payments, 'payments', 'payments.visit_id', '=', 'v.id')
            ->select(array_map(function ($field) { return 'v.'.$field; }, $fields))
            ->selectRaw('COUNT(*) as arrivals, SUM(COALESCE(subscriptions.trials, 0)) as trials, SUM(COALESCE(payments.paid, 0)) as first_paid_subscriptions, MAX(v.created_at) as last_arrived_at')
            ->groupBy(array_map(function ($field) { return 'v.'.$field; }, $fields));
    }

    private function withRevenue($rows, $since, $search = '', $fields = CampaignParameters::FIELDS)
    {
        if ($rows->isEmpty()) return;
        $revenue = $this->visits($since, $search, $fields)->joinSub(
            $this->firstPayments()->select('s.visit_id', 'p.currency', 'p.amount'), 'revenue', 'revenue.visit_id', '=', 'v.id'
        )->where(function ($query) use ($rows, $fields) {
            foreach ($rows as $row) {
                $query->orWhere(function ($query) use ($row, $fields) {
                    foreach ($fields as $field) $query->where('v.'.$field, $row->$field);
                });
            }
        })->select(array_map(function ($field) { return 'v.'.$field; }, $fields))
            ->addSelect('revenue.currency')->selectRaw('SUM(revenue.amount) as amount')
            ->groupBy(array_merge(array_map(function ($field) { return 'v.'.$field; }, $fields), ['revenue.currency']))
            ->orderBy('revenue.currency')->get()->groupBy(function ($row) use ($fields) { return $this->groupKey($row, $fields); });
        foreach ($rows as $row) {
            $row->first_payment_revenue = $revenue->get($this->groupKey($row, $fields), collect())->map(function ($amount) {
                return strtoupper($amount->currency).' '.(in_array($amount->currency, ['usd', 'chf', 'eur', 'gbp'], true)
                    ? number_format($amount->amount / 100, 2, '.', '') : $amount->amount.' minor units');
            })->implode(', ');
        }
    }

    private function groupKey($row, $fields)
    {
        return hash('sha256', json_encode(array_map(function ($field) use ($row) { return $row->$field; }, $fields)));
    }

    private function withLandingPages($rows)
    {
        $ids = [];
        foreach ($rows as $row) {
            $row->landing_page_url = null;
            $row->landing_page_label = 'Not recorded';
            $row->landing_page_location = null;
            if (! CampaignLandingPage::valid($row->landing_url)) continue;
            $path = parse_url($row->landing_url, PHP_URL_PATH);
            $row->landing_page_location = parse_url($row->landing_url, PHP_URL_HOST).$path;
            $row->landing_page_label = $path;
            // Redacted account/token paths are labels, never actionable links.
            if (strpos($path, '[') === false) $row->landing_page_url = $row->landing_url;
            if (preg_match('#^/pieces/([1-9][0-9]*)/?$#D', $path, $match)) $ids[$row->landing_url] = $match[1];
        }
        $pieces = $ids ? \App\Activity\Queries::pieces(array_values(array_unique($ids))) : collect();
        foreach ($rows as $row) {
            if (! isset($ids[$row->landing_url])) continue;
            $id = $ids[$row->landing_url];
            $row->landing_page_url = route('webapp.pieces.show', $id);
            if ($piece = $pieces->get($id)) $row->landing_page_label = $piece->medium_name_with_composer;
        }
    }
}
