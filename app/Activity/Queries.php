<?php

namespace App\Activity;

use Illuminate\Support\Facades\DB;

class Queries
{
    public static function account($alias = 'v', $links = 'l')
    {
        return 'COALESCE('.$alias.'.user_id, '.$links.'.user_id)';
    }

    public static function identity($alias = 'v', $links = 'l')
    {
        $account = self::account($alias, $links);
        $user = DB::getDriverName() === 'sqlite' ? "'u:' || $account" : "CONCAT('u:', $account)";
        $visitor = DB::getDriverName() === 'sqlite' ? "'v:' || $alias.visitor_id" : "CONCAT('v:', $alias.visitor_id)";
        return "CASE WHEN $account IS NOT NULL THEN $user ELSE $visitor END";
    }

    public function visits(Filters $filters = null, $userId = null)
    {
        $query = DB::table('activity_visits as v')->leftJoin('activity_visit_links as l', 'l.visit_id', '=', 'v.id');
        if ($userId !== null) $query->whereRaw(self::account().' = ?', [$userId]);
        if ($filters) {
            $filters->apply($query, 'v.started_at', 'v.platform');
            if ($filters->values['audience'] === 'registered') $query->whereRaw(self::account().' IS NOT NULL');
            if ($filters->values['audience'] === 'anonymous') $query->whereRaw(self::account().' IS NULL');
        }
        return $query;
    }

    public function events(Filters $filters = null, $userId = null)
    {
        $query = DB::table('activity_events as e')->whereNotNull('e.visit_id');
        if ($filters || $userId !== null) $query->whereIn('e.visit_id', $this->visits($filters, $userId)->select('v.id'));
        if ($filters) $filters->apply($query, 'e.occurred_at', 'e.platform');
        return $query;
    }

    public function pieceOpens($query)
    {
        return $query->where('e.type', 'page_view')->where('e.source', 'server')->where('e.resource_type', 'piece')->whereNotNull('e.resource_id');
    }

    public function visitList(Filters $filters = null, $userId = null, $perPage = 25)
    {
        $visits = $this->visits($filters, $userId)->leftJoin('users as u', DB::raw(self::account()), '=', 'u.id')
            ->select('v.*', 'u.first_name', 'u.last_name')->selectRaw(self::account().' as account_id, l.reason as association_reason')
            ->selectSub(DB::table('activity_pages as p')->selectRaw('COUNT(*)')->whereColumn('p.visit_id', 'v.id'), 'pages_count')
            ->orderByDesc('v.started_at')->orderByDesc('v.id')->paginate($perPage, ['*'], 'visits_page')->withQueryString();
        $this->decorateVisits($visits->getCollection());
        return $visits;
    }

    public function decorateVisits($visits)
    {
        $counts = DB::table('activity_events')->whereIn('visit_id', $visits->pluck('id'))->whereIn('type', ['page_view', 'media_play', 'score_opened', 'annotation_saved', 'favorite_changed', 'folder_created', 'trial_started', 'subscription_paid', 'premium_opened'])
            ->select('visit_id', 'type')->selectRaw('COUNT(*) as total')->groupBy('visit_id', 'type')->get()->groupBy('visit_id');
        $pieces = DB::table('activity_events as e')->whereIn('visit_id', $visits->pluck('id'));
        $pieces = $this->pieceOpens($pieces)->select('visit_id')->selectRaw('COUNT(*) as total')->groupBy('visit_id')->get()->keyBy('visit_id');
        $saved = DB::table('activity_events')->whereIn('visit_id', $visits->pluck('id'))->where('type', 'favorite_changed')->where('source', 'server')
            ->select('visit_id', 'metadata->result as result', 'metadata->folder_id as folder')->selectRaw('COUNT(*) as total')->groupBy('visit_id', 'metadata->result', 'metadata->folder_id')->get()->groupBy('visit_id');
        foreach ($visits as $visit) {
            $visit->counts = ($counts[$visit->id] ?? collect())->pluck('total', 'type')->all();
            $visit->pieces_count = (int) ($pieces[$visit->id]->total ?? 0);
            $visit->saved_actions = ($saved[$visit->id] ?? collect())->map(function ($row) {
                if ($row->folder !== null && $row->folder !== 'null') return 'Changed pieces in a folder';
                return in_array($row->result, [true, 1, 'true', '1'], true) ? 'Added a favorite' : ($row->result !== null ? 'Removed a favorite' : 'Changed saved pieces');
            })->unique()->values()->all();
            $visit->summary = $this->summary($visit);
        }
        return $visits;
    }

    public function summary($visit)
    {
        $parts = [];
        if ($visit->pieces_count) $parts[] = 'Opened '.$visit->pieces_count.' '.($visit->pieces_count === 1 ? 'piece' : 'pieces');
        elseif (!empty($visit->pages_count)) $parts[] = 'Viewed '.$visit->pages_count.' '.($visit->pages_count === 1 ? 'page' : 'pages');
        $labels = ['media_play' => 'Played media', 'score_opened' => 'Opened a score', 'annotation_saved' => 'Saved annotations', 'folder_created' => 'Created a folder', 'trial_started' => 'Started a trial', 'subscription_paid' => 'Subscription payment confirmed', 'premium_opened' => 'Viewed Premium options'];
        foreach ($labels as $key => $label) if (!empty($visit->counts[$key])) $parts[] = $label;
        foreach ($visit->saved_actions ?? [] as $label) $parts[] = $label;
        return $parts ? implode(' · ', $parts) : 'Limited activity details were recorded for this visit.';
    }

    public function metrics(Filters $filters)
    {
        $visits = $this->visits($filters);
        $returning = (clone $visits)->whereExists(function ($previous) {
            $previous->selectRaw('1')->from('activity_visits as previous')->leftJoin('activity_visit_links as pl', 'pl.visit_id', '=', 'previous.id')
                ->whereRaw(self::identity('previous', 'pl').' = '.self::identity())
                ->whereColumn('previous.started_at', '<', 'v.started_at');
        });
        return [
            'Unique visitors' => (clone $visits)->selectRaw('COUNT(DISTINCT '.self::identity().') as total')->value('total'),
            'Visits' => (clone $visits)->count(),
            'Returning visitors' => $returning->selectRaw('COUNT(DISTINCT '.self::identity().') as total')->value('total'),
            'Pieces opened' => $this->pieceOpens($this->events($filters))->count(),
            'Media starts' => $this->events($filters)->where('e.type', 'media_play')->count(),
        ];
    }

    public function trend(Filters $filters)
    {
        $rows = $this->visits($filters)->selectRaw('DATE(v.started_at) as day, COUNT(*) as total')->groupBy('day')->get()->keyBy('day');
        $days = [];
        for ($day = $filters->start->copy(); $day->lt($filters->end); $day->addDay()) $days[] = ['date' => $day->format('M j'), 'total' => (int) ($rows[$day->toDateString()]->total ?? 0)];
        return $days;
    }

    public function ungrouped(Filters $filters, $userId = null)
    {
        $query = DB::table('activity_events')->whereNull('visit_id');
        $filters->apply($query, 'occurred_at');
        if ($userId !== null) $query->where('user_id', $userId);
        if ($filters->values['audience'] === 'registered') $query->whereNotNull('user_id');
        if ($filters->values['audience'] === 'anonymous') $query->whereNull('user_id');
        return $query;
    }

    public static function pieces($ids)
    {
        return DB::table('pieces as p')->leftJoin('composers as c', 'c.id', '=', 'p.composer_id')
            ->whereIn('p.id', $ids)->select('p.id', 'p.name', 'c.name as composer')->get()->keyBy('id');
    }
}
