<?php

namespace App\Activity;

use Illuminate\Support\Facades\DB;

class Journey
{
    public function get($id)
    {
        $queries = new Queries;
        $visit = $queries->visits()->where('v.id', $id)->leftJoin('users as u', DB::raw(Queries::account()), '=', 'u.id')
            ->select('v.*', 'u.first_name', 'u.last_name')->selectRaw(Queries::account().' as account_id, l.reason as association_reason')->first();
        abort_unless($visit, 404);
        $visit->pages_count = DB::table('activity_pages')->where('visit_id', $id)->count();
        $queries->decorateVisits(collect([$visit]));
        $pages = DB::table('activity_pages')->where('visit_id', $id)->orderBy('opened_at')->orderBy('id')->paginate(20, ['*'], 'pages_page')->withQueryString();
        // Bound detail payload independently of the size of the visit. Do not let a
        // noisy first page conceal later page groups without a visible notice.
        $eventQuery = DB::table('activity_events')->where('visit_id', $id)->whereIn('page_id', $pages->pluck('id'));
        $detailCount = (clone $eventQuery)->count();
        $events = $eventQuery->orderByRaw('COALESCE(occurred_at, received_at)')->orderBy('sequence')->orderBy('id')->limit(2000)->get();
        $unplaced = DB::table('activity_events')->where('visit_id', $id)->where(function ($q) use ($id) {
            $q->whereNull('page_id')->orWhereNotIn('page_id', DB::table('activity_pages')->where('visit_id', $id)->select('id'));
        })->orderByRaw('COALESCE(occurred_at, received_at)')->orderBy('id')->paginate(50, ['*'], 'events_page')->withQueryString();
        $pieceIds = $events->concat($unplaced->items())->filter(function ($e) { return $e->resource_type === 'piece'; })->pluck('resource_id');
        $pieceIds = $pieceIds->merge($events->concat($unplaced->items())->map(function ($e) { return Presentation::metadata($e)['piece_id'] ?? null; }))->filter()->unique();
        $pieces = Queries::pieces($pieceIds);
        $byPage = $events->groupBy('page_id');
        $groups = $pages->getCollection()->map(function ($page) use ($byPage, $pieces) {
            $all = ($byPage[$page->id] ?? collect())->sort(function ($a, $b) {
                $aTime = Time::event($a); $bTime = Time::event($b);
                return ($aTime->valueOf() <=> $bTime->valueOf()) ?: (($a->sequence ?? 0) <=> ($b->sequence ?? 0)) ?: ($a->id <=> $b->id);
            });
            $opening = $all->first(function ($e) { return $e->type === 'page_view' && $e->resource_type === 'piece'; });
            $piece = $opening ? ($pieces[$opening->resource_id] ?? null) : null;
            $actions = $all->reject(function ($e) { return in_array($e->type, ['page_view', 'page_ready', 'page_checkpoint', 'page_hidden', 'page_visible', 'page_restore', 'request'], true); });
            $lastScore = $actions->where('type', 'score_progress')->last();
            $actions = $actions->filter(function ($e) use ($lastScore) { return $e->type !== 'score_progress' || $e === $lastScore; });
            // A single compact playback summary per playback identity; keep the
            // transitions, but omit repetitive heartbeat progress rows.
            $media = $all->filter(function ($e) { return strpos($e->type, 'media_') === 0; })->groupBy(function ($e) {
                $m = Presentation::metadata($e);
                return ($m['playback_id'] ?? $m['media_id'] ?? 'unknown').':'.($m['piece_id'] ?? '');
            })->map(function ($items) {
                $metadata = $items->map(function ($e) { return Presentation::metadata($e); });
                $coverage = $metadata->pluck('unique_watched_percent')->filter(function ($value) { return is_numeric($value); })->max();
                return ['label' => Presentation::action($items->first()), 'watched' => $metadata->pluck('played_seconds')->max(), 'coverage' => $coverage, 'seeked' => $items->contains('type', 'media_seek')];
            });
            $observed = $all->contains(function ($e) { return in_array($e->type, ['page_ready', 'page_checkpoint', 'page_hidden', 'page_leave'], true); });
            return array_merge(compact('page', 'piece', 'actions', 'media', 'observed'), ['kind' => 'page']);
        });
        $timeline = $groups->concat(collect($unplaced->items())->map(function ($event) { return ['kind' => 'action', 'event' => $event]; }))
            ->sortBy(function ($item) { return $item['kind'] === 'page' ? Time::date($item['page'], 'opened_at')->valueOf() : Time::event($item['event'])->valueOf(); })->values();
        $siblings = $queries->visits();
        if ($visit->account_id) $siblings->whereRaw(Queries::account().' = ?', [$visit->account_id]);
        else $siblings->where('v.visitor_id', $visit->visitor_id)->whereRaw(Queries::account().' IS NULL');
        $previous = (clone $siblings)->where(function ($q) use ($visit) { $q->where('v.started_at', '<', $visit->started_at)->orWhere(function ($q) use ($visit) { $q->where('v.started_at', $visit->started_at)->where('v.id', '<', $visit->id); }); })->orderByDesc('v.started_at')->orderByDesc('v.id')->value('v.id');
        $next = (clone $siblings)->where(function ($q) use ($visit) { $q->where('v.started_at', '>', $visit->started_at)->orWhere(function ($q) use ($visit) { $q->where('v.started_at', $visit->started_at)->where('v.id', '>', $visit->id); }); })->orderBy('v.started_at')->orderBy('v.id')->value('v.id');
        return compact('visit', 'pages', 'groups', 'timeline', 'pieces', 'unplaced', 'previous', 'next', 'detailCount');
    }
}
