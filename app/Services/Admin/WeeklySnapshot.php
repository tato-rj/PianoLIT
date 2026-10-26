<?php

namespace App\Services\Admin;

use App\Activity\{ExcludedAccounts, Queries};
use App\Analytics\MembershipReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Read-only aggregates. No account lists, provider calls or mobile writes. */
class WeeklySnapshot
{
    const TIMEZONE = 'America/New_York';

    public function build(?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?: now())->copy()->timezone(self::TIMEZONE);
        // Sunday's delivery covers the seven completed days, Sunday–Saturday.
        $end = $asOf->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay();
        $start = $end->copy()->subWeek();
        $previousStart = $start->copy()->subWeek();
        $active = $this->accounts($start, $end);
        $previous = $this->accounts($previousStart, $start);
        $new = $this->newAccounts($start, $end);
        $activeCount = (clone $active)->count();
        $previousCount = (clone $previous)->count();
        $newCount = (clone $new)->count();
        $returning = (clone $active)->whereIn('account_id', (clone $previous)->select('account_id'))->count();
        $explorers = $this->pieceAccounts($start, $end);
        $activated = (clone $new)->whereIn('u.id', (clone $explorers)->select('account_id'))->count();
        $origins = (clone $new)->select('u.origin')->selectRaw('COUNT(*) as total')->groupBy('u.origin')->pluck('total', 'origin');

        $web = $this->web($start, $end);
        $ios = $this->ios($start, $end);
        $webPeople = function ($type, $source) use ($web) {
            return (clone $web)->where('e.type', $type)->where('e.source', $source)->distinct()->count('u.id');
        };
        $saved = (clone $web)->where('e.type', 'favorite_changed')->where('e.source', 'server')
            ->where('e.metadata->result', true)->distinct()->count('u.id');

        $members = DB::query()->fromSub(app(MembershipReport::class)->current(), 'm');
        $states = (clone $members)->select('membership_status')->selectRaw('COUNT(*) as total')->groupBy('membership_status')->pluck('total', 'membership_status');
        $withAccess = (clone $members)->whereIn('membership_status', ['active', 'grace_period', 'trial', 'ending']);
        $memberTotal = (clone $withAccess)->count();
        $memberActive = (clone $withAccess)->whereIn('user_id', (clone $active)->select('account_id'))->count();

        $days = [];
        for ($day = $start->copy(); $day->lt($end); $day->addDay()) {
            $days[] = ['label' => $day->format('D'), 'date' => $day->toDateString(),
                'total' => $this->accounts($day, $day->copy()->addDay())->count()];
        }

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->copy()->subDay()->toDateString(),
                'label' => $start->format('M j').'–'.$end->copy()->subDay()->format('M j, Y'),
                'previous_label' => $previousStart->format('M j').'–'.$start->copy()->subDay()->format('M j'),
                'as_of' => $asOf->format('M j, g:i A T')],
            'new_accounts' => $this->comparison($newCount, $this->newAccounts($previousStart, $start)->count()),
            'origins' => ['webapp' => (int) ($origins['webapp'] ?? 0), 'ios' => (int) ($origins['ios'] ?? 0),
                'web' => (int) ($origins['web'] ?? 0), 'other' => (int) $origins->except(['webapp', 'ios', 'web'])->sum()],
            'active_accounts' => $this->comparison($activeCount, $previousCount),
            'returning' => ['total' => $returning, 'base' => $previousCount, 'percent' => $this->percent($returning, $previousCount)],
            'activated' => ['total' => $activated, 'base' => $newCount, 'percent' => $this->percent($activated, $newCount)],
            'days' => $days,
            'web' => ['active' => (clone $web)->distinct()->count('u.id'),
                'explorers' => $this->webPieces($start, $end)->distinct()->count('u.id'),
                'players' => $webPeople('media_play', 'browser'), 'readers' => $webPeople('score_opened', 'browser'), 'savers' => $saved],
            'ios' => ['active' => (clone $ios)->distinct()->count('u.id'), 'requests' => (clone $ios)->count(),
                'piece_requests' => $this->iosRoute(clone $ios, 'api.pieces.find', '/api/pieces/find')->count(),
                'search_requests' => $this->iosRoute(clone $ios, 'api.search', '/api/search')->count()],
            'members' => ['total' => $memberTotal, 'engaged' => $memberActive, 'percent' => $this->percent($memberActive, $memberTotal),
                'active' => (int) ($states['active'] ?? 0) + (int) ($states['grace_period'] ?? 0),
                'trial' => (int) ($states['trial'] ?? 0), 'ending' => (int) ($states['ending'] ?? 0),
                'review' => (int) ($states['needs_review'] ?? 0) + (int) ($states['pending'] ?? 0) + (int) ($states['payment_issue'] ?? 0)],
            'pieces' => $this->topPieces($start, $end),
        ];
    }

    private function comparison(int $current, int $previous): array
    {
        return ['total' => $current, 'previous' => $previous, 'change' => $current - $previous];
    }

    private function percent(int $value, int $base): ?int
    {
        return $base ? (int) round(100 * $value / $base) : null;
    }

    private function newAccounts(Carbon $start, Carbon $end)
    {
        return ExcludedAccounts::users(DB::table('users as u'), 'u.email')
            ->where('u.created_at', '>=', $start->copy()->timezone(config('app.timezone')))
            ->where('u.created_at', '<', $end->copy()->timezone(config('app.timezone')));
    }

    private function period($query, Carbon $start, Carbon $end)
    {
        // Prefer actual instants across DST; imported rows can have local time only.
        return $query->where(function ($q) use ($start, $end) {
            $q->where(function ($utc) use ($start, $end) {
                $utc->where('e.occurred_at_utc', '>=', $start->copy()->utc())
                    ->where('e.occurred_at_utc', '<', $end->copy()->utc());
            })->orWhere(function ($legacy) use ($start, $end) {
                $legacy->whereNull('e.occurred_at_utc')
                    ->where('e.occurred_at', '>=', $start->copy()->timezone(config('app.timezone')))
                    ->where('e.occurred_at', '<', $end->copy()->timezone(config('app.timezone')));
            });
        });
    }

    private function web(Carbon $start, Carbon $end)
    {
        $query = ExcludedAccounts::events(DB::table('activity_events as e'), 'e')
            ->join('activity_visits as v', 'v.id', '=', 'e.visit_id')
            ->leftJoin('activity_visit_links as l', 'l.visit_id', '=', 'v.id')
            ->join('users as u', 'u.id', '=', DB::raw('COALESCE(e.user_id, '.Queries::account().')'))
            ->where('e.platform', 'webapp')->where('v.platform', 'webapp')
            ->where(function ($q) {
                $q->where('e.source', 'browser')->orWhere(function ($server) {
                    $server->where('e.source', 'server')->whereIn('e.type', ['page_view', 'favorite_changed', 'annotation_saved', 'folder_created']);
                });
            });
        ExcludedAccounts::visits($query);
        ExcludedAccounts::users($query, 'u.email');
        return $this->period($query, $start, $end);
    }

    private function ios(Carbon $start, Carbon $end)
    {
        $query = ExcludedAccounts::events(DB::table('activity_events as e'), 'e')
            ->join('users as u', 'u.id', '=', 'e.user_id')->whereNull('e.visit_id')
            ->where('e.platform', 'app')->whereIn('e.type', ['request', 'legacy_request']);
        ExcludedAccounts::users($query, 'u.email');
        return $this->period($query, $start, $end);
    }

    private function accounts(Carbon $start, Carbon $end)
    {
        $web = $this->web($start, $end)->select('u.id as account_id');
        $ios = $this->ios($start, $end)->select('u.id as account_id');
        return DB::query()->fromSub($web->union($ios), 'accounts');
    }

    private function webPieces(Carbon $start, Carbon $end)
    {
        return (new Queries)->pieceOpens($this->web($start, $end));
    }

    private function iosRoute($query, string $name, string $path)
    {
        // The original POST /pieces/find and Redis imports have no route name.
        return $query->where(function ($q) use ($name, $path) {
            $q->where('e.route_name', $name)->orWhere(function ($unnamed) use ($path) {
                $unnamed->whereNull('e.route_name')->where(function ($url) use ($path) {
                    $url->where('e.url', 'like', '%'.$path)->orWhere('e.url', 'like', '%'.$path.'?%');
                });
            });
        });
    }

    private function pieceAccounts(Carbon $start, Carbon $end)
    {
        $web = $this->webPieces($start, $end)->join('pieces as p', 'p.id', '=', 'e.resource_id')
            ->select('u.id as account_id', 'p.id as piece_id')->selectRaw("'webapp' as platform");
        $search = DB::connection()->getQueryGrammar()->wrap('e.metadata->data->search');
        $ios = $this->iosRoute($this->ios($start, $end), 'api.pieces.find', '/api/pieces/find')
            // String equality prevents MySQL coercing malformed IDs like "12x" to 12.
            ->join('pieces as p', function ($join) use ($search) {
                $join->whereRaw('CAST(p.id AS CHAR) = CAST('.$search.' AS CHAR)');
            })->select('u.id as account_id', 'p.id as piece_id')->selectRaw("'app' as platform");
        return DB::query()->fromSub($web->union($ios), 'explored');
    }

    private function topPieces(Carbon $start, Carbon $end): array
    {
        $ranked = $this->pieceAccounts($start, $end)->select('piece_id')
            ->selectRaw("COUNT(DISTINCT account_id) as total,
                COUNT(DISTINCT CASE WHEN platform = 'webapp' THEN account_id END) as web,
                COUNT(DISTINCT CASE WHEN platform = 'app' THEN account_id END) as ios")
            ->groupBy('piece_id')->orderByDesc('total')->orderBy('piece_id')->limit(3)->get();
        $titles = Queries::pieces($ranked->pluck('piece_id'));
        $covers = DB::table('pieces')->whereIn('id', $ranked->pluck('piece_id'))->pluck('cover_path', 'id');
        return $ranked->map(function ($row) use ($titles, $covers) {
            $piece = $titles[$row->piece_id];
            return ['id' => (int) $row->piece_id, 'title' => $piece->medium_name_with_composer,
                'cover' => $covers[$row->piece_id] ?? null, 'total' => (int) $row->total,
                'web' => (int) $row->web, 'ios' => (int) $row->ios];
        })->all();
    }
}
