<?php

namespace App\Analytics;

use App\Support\AdminPagination;
use App\Billing\Sources\{Apple, Stripe};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};

class MembershipReport
{
    const LABELS = [
        'active' => 'Active', 'trial' => 'Trial', 'ending' => 'Ending',
        'grace_period' => 'Billing grace period', 'expired' => 'Expired',
        'revoked' => 'Refunded / revoked', 'paused' => 'Paused',
        'payment_issue' => 'Payment issue', 'needs_review' => 'Needs review',
        'pending' => 'Not checked', 'sandbox' => 'Sandbox purchase',
    ];

    /** The same read-only current status used by the admin membership directory. */
    public function current()
    {
        return $this->query(Schema::hasTable('apple_membership_checks') && Schema::hasTable('apple_membership_sync_runs'));
    }

    public function activeTotals($start, $end)
    {
        $ready = Schema::hasTable('apple_membership_checks') && Schema::hasTable('apple_membership_sync_runs');
        return DB::query()->fromSub($this->query($ready), 'members')
            ->whereIn('membership_status', ['active', 'grace_period'])
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN membership_started_at >= ? AND membership_started_at <= ? THEN 1 ELSE 0 END) as added', [$start, $end])
            ->first();
    }

    public function get(Request $request)
    {
        $filters = $request->validate([
            'provider' => 'nullable|in:stripe,apple,super_user,billing',
            'status' => 'nullable|in:all,active,trial,ending,grace_period,expired,revoked,paused,payment_issue,needs_review,pending,sandbox',
            'search' => 'nullable|string|max:160',
            'sort' => 'nullable|in:date_asc,date_desc,name,checked',
        ]);
        // Missing status opens Active; an explicit empty status keeps older All links working.
        $filters['status'] = array_key_exists('status', $filters) ? ($filters['status'] ?: 'all') : 'active';
        $ready = Schema::hasTable('apple_membership_checks') && Schema::hasTable('apple_membership_sync_runs');
        $base = $this->query($ready);
        $superUsers = app(SuperUserReport::class)->query();
        $summary = DB::query()->fromSub(clone $base, 'members')->select('provider', 'membership_status')->selectRaw('COUNT(*) as total')->groupBy('provider', 'membership_status')->get();
        $counts = [];
        foreach (['stripe', 'apple'] as $provider) {
            $counts[$provider] = array_fill_keys(array_keys(self::LABELS), 0);
            foreach ($summary->where('provider', $provider) as $row) $counts[$provider][$row->membership_status] = (int) $row->total;
        }
        $totals = [];
        foreach (self::LABELS as $key => $label) $totals[$key] = $counts['stripe'][$key] + $counts['apple'][$key];
        $counts['super_user'] = array_fill_keys(array_keys(self::LABELS), 0);
        $counts['super_user']['active'] = DB::query()->fromSub(clone $superUsers, 'super_users')->count();
        // Show each account once in the directory, while preserving its billing totals above.
        $directory = (clone $base)->where('u.super_user', false)->unionAll($superUsers);
        $rows = DB::query()->fromSub($directory, 'members');
        if (($filters['provider'] ?? '') === 'billing') $rows->whereIn('provider', ['stripe', 'apple']);
        elseif (! empty($filters['provider'])) $rows->where('provider', $filters['provider']);
        if ($filters['status'] !== 'all') {
            $filters['status'] === 'needs_review'
                ? $rows->whereIn('membership_status', ['needs_review', 'pending', 'payment_issue'])
                : $rows->where('membership_status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $search = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['search']).'%';
            $rows->where(function ($q) use ($search, $filters) {
                $q->whereRaw("email LIKE ? ESCAPE '!'", [$search])->orWhere(function ($names) use ($filters) {
                    foreach (preg_split('/\s+/', trim($filters['search']), -1, PREG_SPLIT_NO_EMPTY) as $part) {
                        $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $part).'%';
                        $names->where(function ($name) use ($term) {
                            $name->whereRaw("first_name LIKE ? ESCAPE '!'", [$term])->orWhereRaw("last_name LIKE ? ESCAPE '!'", [$term]);
                        });
                    }
                });
                if (ctype_digit($filters['search'])) $q->orWhere('user_id', (int) $filters['search']);
            });
        }
        switch ($filters['sort'] ?? 'date_asc') {
            case 'name': $rows->orderBy('first_name')->orderBy('last_name'); break;
            case 'checked': $rows->orderByDesc('checked_at'); break;
            case 'date_desc': $rows->orderByDesc('access_until'); break;
            default: $rows->orderByRaw('CASE WHEN access_until IS NULL THEN 1 ELSE 0 END')->orderBy('access_until');
        }
        return [
            'memberships' => $rows->orderBy('membership_id')->orderBy('user_id')->paginate(AdminPagination::PER_PAGE)->withQueryString()->appends(['status' => $filters['status']]),
            'counts' => $counts, 'totals' => $totals, 'filters' => $filters, 'labels' => self::LABELS,
            'monitorReady' => $ready,
            'lastRun' => $ready ? DB::table('apple_membership_sync_runs')->orderByDesc('id')->first() : null,
        ];
    }

    private function query($ready)
    {
        $now = now()->toDateTimeString();
        $q = \App\Activity\ExcludedAccounts::users(DB::table('memberships as m')->join('users as u', 'u.id', '=', 'm.user_id'), 'u.email')
            // Match the existing hasOne relation: one membership record per account.
            ->joinSub(DB::table('memberships')->selectRaw('MIN(id) as id')->groupBy('user_id'), 'owned', function ($join) { $join->on('owned.id', '=', 'm.id'); })
            ->whereIn('m.source_type', [Stripe::class, Apple::class])
            ->leftJoin('stripe_memberships as s', function ($j) { $j->on('s.id', '=', 'm.source_id')->where('m.source_type', Stripe::class); })
            ->leftJoin('apple_memberships as a', function ($j) { $j->on('a.id', '=', 'm.source_id')->where('m.source_type', Apple::class); })
            ->where(function ($q) { $q->whereNotNull('s.id')->orWhereNotNull('a.id'); })
            ->where(function ($q) { $q->where('m.source_type', Stripe::class)->orWhereNotIn('u.id', config('apple-membership-monitor.excluded_user_ids')); });
        if ($ready) $q->leftJoin('apple_membership_checks as c', 'c.apple_membership_id', '=', 'a.id');

        $stripeStatus = "CASE
            WHEN s.ended_at IS NOT NULL THEN 'expired'
            WHEN s.membership_ends_at IS NOT NULL THEN CASE WHEN s.membership_ends_at > ? THEN 'ending' ELSE 'expired' END
            WHEN s.paused_at IS NOT NULL THEN 'paused'
            WHEN s.status IN ('past_due', 'unpaid', 'incomplete') THEN 'payment_issue'
            WHEN s.status IN ('canceled', 'incomplete_expired') THEN 'expired'
            WHEN s.renews_at IS NULL THEN 'needs_review'
            WHEN s.renews_at <= ? THEN 'expired'
            WHEN s.status IN ('trialing', 'trial') THEN 'trial'
            WHEN s.status = 'active' THEN 'active'
            ELSE 'needs_review' END";
        $appleStatus = $ready ? "CASE
            WHEN c.apple_membership_id IS NULL OR c.source_updated_at IS NULL OR c.source_updated_at <> a.updated_at THEN 'pending'
            WHEN c.status = 'needs_review' OR c.checked_at < ? THEN 'needs_review'
            WHEN c.environment = 'Sandbox' THEN 'sandbox'
            WHEN c.status = 'revoked' THEN 'revoked'
            WHEN c.grace_expires_at > ? AND c.expires_at <= ? THEN 'grace_period'
            WHEN c.expires_at <= ? THEN 'expired'
            WHEN c.status IN ('active', 'trial') AND c.auto_renews = 0 THEN 'ending'
            WHEN c.status = 'trial' THEN 'trial'
            WHEN c.status = 'active' THEN 'active'
            ELSE 'needs_review' END" : "'pending'";
        $q->select(['m.id as membership_id', 'm.created_at as membership_started_at', 'm.user_id', 'u.first_name', 'u.last_name', 'u.email', 'u.super_user'])
            ->selectRaw("CASE WHEN s.id IS NOT NULL THEN 'stripe' ELSE 'apple' END AS provider")
            ->selectRaw("CASE WHEN s.id IS NOT NULL THEN {$stripeStatus} ELSE {$appleStatus} END AS membership_status", $ready ? [$now, $now, now()->subHours(48)->toDateTimeString(), $now, $now, $now] : [$now, $now])
            ->selectRaw($ready ? 'COALESCE(s.plan, c.product_id, a.plan) as plan' : 'COALESCE(s.plan, a.plan) as plan')
            ->selectRaw($ready ? "CASE WHEN s.id IS NOT NULL THEN COALESCE(s.ended_at, s.paused_at, s.membership_ends_at, s.renews_at) WHEN c.status = 'revoked' THEN NULL ELSE COALESCE(c.grace_expires_at, c.expires_at) END AS access_until" : 'COALESCE(s.ended_at, s.paused_at, s.membership_ends_at, s.renews_at) AS access_until')
            ->selectRaw($ready ? 'c.checked_at, c.verified_at, c.next_check_at, c.error_code, c.environment, c.status as check_status' : 'NULL as checked_at, NULL as verified_at, NULL as next_check_at, NULL as error_code, NULL as environment, NULL as check_status')
            ->selectRaw('CASE WHEN s.id IS NOT NULL THEN s.updated_at ELSE a.updated_at END as source_updated_at');
        return $q;
    }

    public static function errorText($code)
    {
        return [
            '21002' => 'Apple could not read this receipt. It will be retried.',
            '21003' => 'Apple could not authenticate the saved receipt.',
            '21004' => 'The Apple shared secret does not match. Check the server configuration.',
            '21005' => 'Apple is temporarily unavailable. It will be retried.',
            '21009' => 'Apple reported a temporary error. It will be retried.',
            '21010' => 'Apple cannot find the purchasing account, or it was deleted. Current membership status is unknown.',
            'connection_failed' => 'The connection to Apple failed. It will be retried.',
            'invalid_response' => 'Apple returned an incomplete response. It will be retried.',
            'missing_receipt' => 'No saved receipt or shared secret is available.',
            'bundle_mismatch' => 'The receipt belongs to a different app. Check the configured bundle ID.',
            'subscription_not_found' => 'The saved subscription could not be identified in Apple’s response.',
        ][$code] ?? 'Apple could not verify this receipt. It will be retried.';
    }

    public static function planName($plan)
    {
        $suffix = strtolower(last(explode('.', (string) $plan)));
        if (in_array($suffix, ['monthly', 'monthlyplan', 'monthplan'], true)) return 'Monthly';
        if (in_array($suffix, ['yearly', 'yearlyplan', 'yearplan', 'annual'], true)) return 'Yearly';
        return $plan ?: 'Plan not recorded';
    }
}
