<?php

namespace App\Activity;

use Illuminate\Support\Facades\DB;

class Insights
{
    public function get(Filters $filters)
    {
        $q = new Queries;
        $opens = $q->pieceOpens($q->events($filters));
        $pieces = (clone $opens)->join('pieces as p', 'p.id', '=', 'e.resource_id')->select('p.id', 'p.name')->selectRaw('COUNT(*) as total')->groupBy('p.id', 'p.name')->orderByDesc('total')->limit(10)->get();
        $composers = (clone $opens)->join('pieces as p', 'p.id', '=', 'e.resource_id')->join('composers as c', 'c.id', '=', 'p.composer_id')->select('c.id', 'c.name')->selectRaw('COUNT(*) as total')->groupBy('c.id', 'c.name')->orderByDesc('total')->limit(8)->get();
        $tags = (clone $opens)->join('piece_tag as pt', 'pt.piece_id', '=', 'e.resource_id')->join('tags as t', 't.id', '=', 'pt.tag_id')->whereIn('t.type', ['genre', 'level'])->select('t.name', 't.type')->selectRaw('COUNT(*) as total')->groupBy('t.id', 't.name', 't.type')->orderByDesc('total')->limit(12)->get();
        $pages = $q->events($filters)->where('e.type', 'page_view')->select('e.route_name')->selectRaw('COUNT(*) as total')->groupBy('e.route_name')->orderByDesc('total')->limit(8)->get();
        $engagement = $q->events($filters)->whereIn('e.type', ['media_play', 'media_end', 'score_opened', 'annotation_saved', 'favorite_changed', 'folder_created', 'premium_restriction', 'registration_started', 'registration_completed', 'checkout_started', 'trial_started', 'subscription_activated', 'subscription_paid', 'subscription_canceled'])->select('e.type')->selectRaw('COUNT(*) as total')->groupBy('e.type')->get()->pluck('total', 'type');
        $coverage = $q->events($filters)->where('e.type', 'like', 'media_%')->whereNotNull('e.metadata->unique_watched_percent')->whereNotNull('e.metadata->playback_id');
        $engagement['meaningful_playback'] = (clone $coverage)->exists() ? $coverage->where('e.metadata->unique_watched_percent', '>=', 50)->distinct()->count('e.metadata->playback_id') : null;
        $engagement['synthesia'] = $engagement->has('media_play') ? $q->events($filters)->where('e.type', 'media_play')->where('e.metadata->media_id', 'like', '%synthesia%')->count() : null;
        $engagement['favorites_added'] = $engagement->has('favorite_changed') ? $q->events($filters)->where('e.type', 'favorite_changed')->where('e.source', 'server')->where('e.metadata->result', true)->whereNull('e.metadata->folder_id')->count() : null;
        $engagement['folder_additions'] = $engagement->has('favorite_changed') ? $q->events($filters)->where('e.type', 'favorite_changed')->where('e.source', 'server')->where('e.metadata->result', true)->whereNotNull('e.metadata->folder_id')->count() : null;
        $finalPages = DB::table('activity_pages as p')->whereIn('p.visit_id', $q->visits($filters)->select('v.id'))->whereNotExists(function ($later) {
            $later->from('activity_pages as later')->selectRaw('1')->whereColumn('later.visit_id', 'p.visit_id')->where(function ($where) { $where->whereColumn('later.opened_at', '>', 'p.opened_at')->orWhere(function ($same) { $same->whereColumn('later.opened_at', 'p.opened_at')->whereColumn('later.id', '>', 'p.id'); }); });
        })->select('p.route_name')->selectRaw('COUNT(*) as total')->groupBy('p.route_name')->orderByDesc('total')->limit(8)->get();
        $repeats = (clone $opens)->join('activity_visits as v', 'v.id', '=', 'e.visit_id')->leftJoin('activity_visit_links as l', 'l.visit_id', '=', 'v.id')
            ->select('e.resource_id')->selectRaw(Queries::identity().' as identity_key, COUNT(*) as total')
            ->groupBy('e.resource_id')->groupByRaw(Queries::identity())->havingRaw('COUNT(DISTINCT e.visit_id) > 1');
        $repeatPieces = DB::query()->fromSub($repeats, 'r')->join('pieces as p', 'p.id', '=', 'r.resource_id')->select('p.id', 'p.name')->selectRaw('COUNT(*) as returning_people, SUM(r.total) as total')->groupBy('p.id', 'p.name')->orderByDesc('returning_people')->limit(8)->get();
        $cohorts = $q->visits($filters)->join('users as u', 'u.id', '=', DB::raw(Queries::account()))->selectRaw('SUBSTR(u.created_at, 1, 7) as cohort, COUNT(*) as visits, COUNT(DISTINCT u.id) as people')->groupBy('cohort')->orderByDesc('cohort')->limit(12)->get();
        // Confirmed outcomes remain account facts, not attribution claims. Same-
        // account ordered steps are correlated; no invented mobile/browser joins.
        $conversions = [];
        foreach ([['registration_started', 'registration_completed'], ['registration_completed', 'trial_started'], ['trial_started', 'subscription_paid'], ['premium_restriction', 'premium_opened'], ['premium_opened', 'subscription_paid']] as $pair) {
            $base = $q->events($filters)->join('activity_visits as v', 'v.id', '=', 'e.visit_id')->leftJoin('activity_visit_links as l', 'l.visit_id', '=', 'v.id')->where('e.type', $pair[0]);
            $started = (clone $base)->selectRaw('COUNT(DISTINCT '.Queries::identity().') as total')->value('total');
            $completed = (clone $base)->whereExists(function ($later) use ($pair, $filters) {
                $later->from('activity_events as next')->selectRaw('1')->where(function ($actor) { $actor->whereColumn('next.visit_id', 'e.visit_id')->orWhereRaw('next.user_id = '.Queries::account()); })
                    ->where('next.type', $pair[1])->whereColumn('next.occurred_at', '>=', 'e.occurred_at')->where('next.occurred_at', '<', $filters->end)
                    ->where('next.source', $pair[1] === 'premium_opened' ? 'browser' : 'server');
                ExcludedAccounts::events($later, 'next');
            })->selectRaw('COUNT(DISTINCT '.Queries::identity().') as total')->value('total');
            $conversions[] = ['from' => $pair[0], 'to' => $pair[1], 'started' => $started, 'completed' => $completed];
        }
        return compact('pieces', 'composers', 'tags', 'pages', 'engagement', 'finalPages', 'repeatPieces', 'cohorts', 'conversions');
    }
}
