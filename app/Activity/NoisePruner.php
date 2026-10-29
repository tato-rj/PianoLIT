<?php

namespace App\Activity;

use Illuminate\Support\Facades\DB;

class NoisePruner
{
    const CONFIRMED_ACTIONS = ['favorite_changed', 'folder_created', 'folder_renamed', 'folder_updated', 'folder_deleted', 'annotation_saved', 'registration_completed', 'login', 'checkout_started', 'trial_started', 'subscription_activated', 'subscription_paid', 'subscription_canceled', 'synthesia_request_created', 'tutorial_request_created'];

    public function candidates($cutoff, $minimumMilliseconds)
    {
        $query = DB::table('activity_visits as v')->whereIn('v.platform', ['web', 'webapp']);
        $utc = $cutoff->copy()->utc()->format('Y-m-d H:i:s.v');
        $local = $cutoff->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s.v');
        $query->where(function ($age) use ($utc, $local) {
            $age->where(function ($canonical) use ($utc) {
                $canonical->whereNotNull('v.started_at_utc')->whereNotNull('v.last_activity_at_utc')->where('v.started_at_utc', '<', $utc)->where('v.last_activity_at_utc', '<', $utc);
            })->orWhere(function ($legacy) use ($local) {
                $legacy->where(function ($missing) { $missing->whereNull('v.started_at_utc')->orWhereNull('v.last_activity_at_utc'); })
                    ->where('v.started_at', '<', $local)->where('v.last_activity_at', '<', $local);
            });
        });
        $difference = function ($start, $last) {
            if (DB::getDriverName() === 'sqlite') return "(strftime('%s', $last) - strftime('%s', $start)) * 1000 + CAST(SUBSTR($last, 21, 3) AS INTEGER) - CAST(SUBSTR($start, 21, 3) AS INTEGER)";
            return "TIMESTAMPDIFF(MICROSECOND, $start, $last) / 1000";
        };
        $span = 'CASE WHEN v.started_at_utc IS NOT NULL AND v.last_activity_at_utc IS NOT NULL THEN ('.$difference('v.started_at_utc', 'v.last_activity_at_utc').') ELSE ('.$difference('v.started_at', 'v.last_activity_at').') END';
        $query->whereRaw('('.$span.') >= 0')->whereRaw('('.$span.') < ?', [$minimumMilliseconds]);
        // Inconsistent old summaries must not erase actual longer browser evidence.
        $query->whereNotExists(function ($pages) use ($minimumMilliseconds, $utc, $local) {
            $pages->from('activity_pages as p')->selectRaw('1')->whereColumn('p.visit_id', 'v.id')->where(function ($time) use ($minimumMilliseconds, $utc, $local) {
                $time->where('p.visible_ms', '>=', $minimumMilliseconds)->orWhere('p.elapsed_ms', '>=', $minimumMilliseconds)
                    ->orWhere('p.opened_at_utc', '>=', $utc)->orWhere(function ($legacy) use ($local) { $legacy->whereNull('p.opened_at_utc')->where('p.opened_at', '>=', $local); });
            });
        })->whereNotExists(function ($events) use ($minimumMilliseconds) {
            $events->from('activity_events as e')->selectRaw('1')->whereColumn('e.visit_id', 'v.id')->where('e.source', 'browser')->where('e.elapsed_ms', '>=', $minimumMilliseconds);
        });
        return $query;
    }

    public function run($cutoff, $minimumMilliseconds, $execute = false, $batch = 200, $limit = 10000)
    {
        $totals = ['visits' => 0, 'pages' => 0, 'events' => 0, 'links' => 0, 'preserved_events' => 0];
        $cursor = null;
        while ($totals['visits'] < $limit) {
            $query = $this->candidates($cutoff, $minimumMilliseconds);
            if ($cursor) $query->where('v.id', '>', $cursor);
            $ids = $query->orderBy('v.id')->limit(min($batch, $limit - $totals['visits']))->pluck('v.id')->all();
            if (!$ids) break;
            $cursor = end($ids);
            $process = function () use ($ids, $cutoff, $minimumMilliseconds, $execute) {
                if ($execute) $ids = $this->candidates($cutoff, $minimumMilliseconds)->whereIn('v.id', $ids)->lockForUpdate()->pluck('v.id')->all();
                $pages = DB::table('activity_pages')->whereIn('visit_id', $ids);
                $events = DB::table('activity_events')->where(function ($query) use ($ids) {
                    $query->whereIn('visit_id', $ids)->orWhereIn('page_id', DB::table('activity_pages')->whereIn('visit_id', $ids)->select('id'));
                });
                $keep = (clone $events)->where(function ($confirmed) {
                    $confirmed->where(function ($server) { $server->where('source', 'server')->whereIn('type', self::CONFIRMED_ACTIONS); })
                        ->orWhere('source', 'redis')->orWhere('platform', 'app');
                });
                $counts = ['visits' => count($ids), 'pages' => (clone $pages)->count(), 'events' => (clone $events)->count() - (clone $keep)->count(),
                    'links' => DB::table('activity_visit_links')->whereIn('visit_id', $ids)->count(), 'preserved_events' => (clone $keep)->count()];
                if ($execute) {
                    $keep->update(['visit_id' => null, 'page_id' => null]);
                    $events->delete();
                    $pages->delete();
                    DB::table('activity_visit_links')->whereIn('visit_id', $ids)->delete();
                    DB::table('activity_visits')->whereIn('id', $ids)->delete();
                }
                return $counts;
            };
            $counts = $execute ? DB::transaction($process) : $process();
            foreach ($counts as $key => $count) $totals[$key] += $count;
        }
        return $totals;
    }
}
