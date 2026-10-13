<?php

namespace App\Activity;

use App\Support\AdminPagination;
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

    public function visitList(Filters $filters = null, $userId = null, $perPage = AdminPagination::PER_PAGE, VisitSort $sort = null)
    {
        $base = $this->visits($filters, $userId);
        $query = (clone $base)->leftJoin('users as u', DB::raw(self::account()), '=', 'u.id')
            ->select('v.*', 'u.first_name', 'u.last_name')->selectRaw(self::account().' as account_id, l.reason as association_reason')
            ->selectSub(DB::table('activity_pages as p')->selectRaw('COUNT(*)')->whereColumn('p.visit_id', 'v.id'), 'pages_count');
        $visits = ($sort ?: new VisitSort)->apply($query, $base->select('v.id'))->paginate($perPage, ['*'], 'visits_page')->withQueryString();
        $this->decorateVisits($visits->getCollection());
        return $visits;
    }

    public function decorateVisits($visits)
    {
        $mediaKind = DB::connection()->getQueryGrammar()->wrap('metadata->media_kind');
        $counts = DB::table('activity_events')->whereIn('visit_id', $visits->pluck('id'))->whereIn('type', ['page_view', 'media_play', 'score_opened', 'annotation_saved', 'favorite_changed', 'folder_created', 'trial_started', 'subscription_paid', 'premium_opened'])
            ->select('visit_id', 'type')->selectRaw("COUNT(*) as total, MAX(CASE WHEN $mediaKind = 'audio' THEN 1 ELSE 0 END) as has_audio, MAX(CASE WHEN $mediaKind = 'video' THEN 1 ELSE 0 END) as has_video, MAX(CASE WHEN $mediaKind IN ('audio', 'video') THEN 0 ELSE 1 END) as has_unknown_media")
            ->groupBy('visit_id', 'type')->get()->groupBy('visit_id');
        $pieces = DB::table('activity_events as e')->whereIn('visit_id', $visits->pluck('id'));
        $pieces = $this->pieceOpens($pieces)->select('visit_id')->selectRaw('COUNT(*) as total')->groupBy('visit_id')->get()->keyBy('visit_id');
        $saved = DB::table('activity_events')->whereIn('visit_id', $visits->pluck('id'))->where('type', 'favorite_changed')->where('source', 'server')
            ->select('visit_id', 'metadata->result as result', 'metadata->folder_id as folder')->selectRaw('COUNT(*) as total')->groupBy('visit_id', 'metadata->result', 'metadata->folder_id')->get()->groupBy('visit_id');
        foreach ($visits as $visit) {
            $visit->counts = ($counts[$visit->id] ?? collect())->pluck('total', 'type')->all();
            $playback = ($counts[$visit->id] ?? collect())->firstWhere('type', 'media_play');
            $visit->media_kinds = array_values(array_filter(['audio', 'video', 'unknown'], function ($kind) use ($playback) {
                $column = $kind === 'unknown' ? 'has_unknown_media' : 'has_'.$kind;
                return $playback && $playback->$column;
            }));
            $visit->pieces_count = (int) ($pieces[$visit->id]->total ?? 0);
            $visit->saved_actions = ($saved[$visit->id] ?? collect())->map(function ($row) {
                if ($row->folder !== null && $row->folder !== 'null') return 'Changed pieces in a folder';
                return in_array($row->result, [true, 1, 'true', '1'], true) ? 'Added a favorite' : ($row->result !== null ? 'Removed a favorite' : 'Changed saved pieces');
            })->unique()->values()->all();
            $visit->summary_parts = $this->summaryParts($visit);
            if (!isset($visit->actions_count)) $visit->actions_count = count($visit->summary_parts);
            $visit->summary = $this->summary($visit);
        }
        return $visits;
    }

    public function summary($visit)
    {
        $parts = $visit->summary_parts ?? $this->summaryParts($visit);
        return $parts ? implode(' · ', array_column($parts, 'label')) : 'Limited activity details were recorded for this visit.';
    }

    public function summaryParts($visit)
    {
        $parts = [];
        if ($visit->pieces_count) {
            $label = $visit->pieces_count.' '.($visit->pieces_count === 1 ? 'piece' : 'pieces');
            $parts[] = ['icon' => 'music', 'tone' => 'pieces', 'label' => $label, 'description' => 'Opened '.$label];
        } elseif (!empty($visit->pages_count)) {
            $label = $visit->pages_count.' '.($visit->pages_count === 1 ? 'page' : 'pages');
            $parts[] = ['icon' => 'file-text', 'tone' => 'pages', 'label' => $label, 'description' => 'Viewed '.$label];
        }
        if (!empty($visit->counts['media_play'])) {
            foreach ($visit->media_kinds ?? ['unknown'] as $kind) {
                $label = ['audio' => 'Audio', 'video' => 'Video'][$kind] ?? 'Media';
                $parts[] = ['icon' => Presentation::icon('media_play', ['media_kind' => $kind]), 'tone' => in_array($kind, ['audio', 'video'], true) ? $kind : 'media', 'label' => $label, 'description' => 'Played '.strtolower($label)];
            }
        }
        $labels = [
            'score_opened' => ['book-open', 'score', 'Score', 'Opened a score'],
            'annotation_saved' => ['pencil', 'annotations', 'Annotations', 'Saved annotations'],
            'folder_created' => ['folder-plus', 'folders', 'New folder', 'Created a folder'],
            'trial_started' => ['gift', 'membership', 'Trial started', 'Started a trial'],
            'subscription_paid' => ['credit-card', 'membership', 'Payment confirmed', 'Subscription payment confirmed'],
            'premium_opened' => ['crown', 'premium', 'Premium', 'Viewed Premium options'],
        ];
        foreach ($labels as $key => [$icon, $tone, $label, $description]) if (!empty($visit->counts[$key])) $parts[] = compact('icon', 'tone', 'label', 'description');
        $savedLabels = ['Changed pieces in a folder' => 'Folder updated', 'Added a favorite' => 'Favorite added', 'Removed a favorite' => 'Favorite removed', 'Changed saved pieces' => 'Saved pieces updated'];
        foreach ($visit->saved_actions ?? [] as $description) {
            $folder = $description === 'Changed pieces in a folder';
            $parts[] = ['icon' => $folder ? 'folder-open' : 'heart', 'tone' => $folder ? 'folders' : 'favorites', 'label' => $savedLabels[$description] ?? $description, 'description' => $description];
        }
        return $parts;
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
        $pieces = $this->pieceOpens($this->events($filters))->selectRaw('DATE(e.occurred_at) as day, COUNT(*) as total')->groupBy('day')->get()->keyBy('day');
        $media = $this->events($filters)->where('e.type', 'media_play')->selectRaw('DATE(e.occurred_at) as day, COUNT(*) as total')->groupBy('day')->get()->keyBy('day');
        $days = [];
        for ($day = $filters->start->copy(); $day->lt($filters->end); $day->addDay()) {
            $key = $day->toDateString();
            $days[] = ['day' => $key, 'date' => $day->format('M j'), 'total' => (int) ($rows[$key]->total ?? 0),
                'pieces' => (int) ($pieces[$key]->total ?? 0), 'media_starts' => (int) ($media[$key]->total ?? 0)];
        }
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
            ->whereIn('p.id', $ids)->select('p.id', 'p.name', 'p.nickname', 'p.key', 'p.collection_name', 'p.collection_number',
                'p.movement_number', 'p.catalogue_name', 'p.catalogue_number', 'c.name as composer')->get()->map(function ($row) {
                // Reuse the established name accessors without loading default
                // relations/counts or serializing the models' appended fields.
                $attributes = (array) $row; unset($attributes['composer']);
                $piece = (new \App\Piece)->setRawAttributes($attributes);
                $composer = (new \App\Composer)->setRawAttributes(['name' => $row->composer]);
                $piece->setRelation('composer', $composer);
                $row->medium_name_with_composer = $row->composer ? $piece->medium_name_with_composer : $piece->medium_name;
                return $row;
            })->keyBy('id');
    }
}
