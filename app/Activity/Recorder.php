<?php

namespace App\Activity;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Crypt, DB};
use Illuminate\Support\Str;

class Recorder
{
    public function eligible(Request $request)
    {
        return config('activity.enabled') && $request->hasSession()
            && $this->platform($request) === 'webapp'
            && !auth('admin')->check()
            && $request->getHost() !== 'admin.'.config('app.short_url')
            && !$request->routeIs('activity.*', 'admin.*', 'api.*')
            && !TrafficFilter::ignores($request) && !ExcludedAccounts::request($request);
    }

    public function visitor(Request $request)
    {
        if (!$request->session()->has('activity.visitor')) $request->session()->put('activity.visitor', (string) Str::uuid());
        return $request->session()->get('activity.visitor');
    }

    public function platform(Request $request)
    {
        return $request->getHost() === 'my.'.config('app.short_url') ? 'webapp' : 'web';
    }

    public function context(Request $request)
    {
        $token = $request->header('X-Activity-Page') ?: $request->input('_activity_page');
        return $token ? $this->pageFromToken($request, $token) : null;
    }

    public function pageFromToken(Request $request, $token)
    {
        if (ExcludedAccounts::request($request)) return null;
        if (!is_string($token) || strlen($token) > 4096) return null;
        try {
            $id = Crypt::decryptString($token);
            $pending = (new PendingVisits)->find($request, $id);
            if ($pending) $id = $pending->id;
            elseif (strpos($id, PendingVisits::PREFIX) === 0) return null;
            $page = DB::table('activity_pages')->where('id', $id)
                ->where('visitor_id', $request->session()->get('activity.visitor', ''))
                ->where('user_id', auth('web')->id())
                ->where('platform', $this->platform($request))
                ->where(function ($query) {
                    $query->where('opened_at_utc', '>=', now('UTC')->subHours(config('activity.page_token_hours')))
                        ->orWhere(function ($legacy) { $legacy->whereNull('opened_at_utc')->where('opened_at', '>=', now()->subHours(config('activity.page_token_hours'))); });
                })->first();
            return $page ?: $pending;
        } catch (\Illuminate\Contracts\Encryption\DecryptException $exception) {
            return null;
        }
    }

    public function openPage(Request $request)
    {
        if (ExcludedAccounts::request($request)) return null;
        $visitor = $request->attributes->get('activity.visitor') ?: $this->visitor($request);
        $user = auth('web')->id();
        $platform = $this->platform($request);
        // Anchor in UTC before converting: the installed legacy Carbon version
        // can select the earlier offset when now() falls in a repeated local hour.
        $now = now('UTC')->timezone(config('app.timezone'));
        $visit = DB::table('activity_visits')->where('id', $request->session()->get('activity.visit', ''))
            ->where('visitor_id', $visitor)->where('user_id', $user)
            ->where('platform', $platform)
            ->where(function ($query) use ($now) {
                $query->where('last_interaction_at_utc', '>=', $now->copy()->utc()->subMinutes(config('activity.visit_idle_minutes')))
                    ->orWhere(function ($legacy) use ($now) {
                        $legacy->whereNull('last_interaction_at_utc')->whereRaw('COALESCE(last_interaction_at, last_activity_at) >= ?', [$now->copy()->subMinutes(config('activity.visit_idle_minutes'))]);
                    });
            })->first();
        if ($visit && TrafficFilter::minimumMilliseconds() > 0 && !PendingVisits::qualified($visit->id)) {
            // An old server-only visit is not evidence of browser engagement.
            // Requalify subsequent navigation rather than grandfathering bots.
            $visit = null;
            $request->session()->forget(['activity.visit', PendingVisits::KEY]);
        }
        $pending = $visit ? null : (new PendingVisits)->visit($request, $request->session()->get('activity.visit', ''), $visitor, $user, $platform);
        $visitId = $visit ? $visit->id : ($pending['id'] ?? (string) Str::uuid());
        $visitRow = $pending ?: [
            'id' => $visitId, 'user_id' => $user, 'visitor_id' => $visitor, 'platform' => $platform,
            'started_at' => $now->format('Y-m-d H:i:s.v'), 'last_activity_at' => $now->format('Y-m-d H:i:s.v'),
            'started_at_utc' => $now->copy()->utc()->format('Y-m-d H:i:s.v'), 'last_activity_at_utc' => $now->copy()->utc()->format('Y-m-d H:i:s.v'),
            'last_interaction_at' => $now->format('Y-m-d H:i:s.v'), 'last_interaction_at_utc' => $now->copy()->utc()->format('Y-m-d H:i:s.v'),
        ];
        $page = [
            'id' => (string) Str::uuid(), 'visit_id' => $visitId, 'user_id' => $user, 'visitor_id' => $visitor,
            'platform' => $platform, 'url' => Metadata::url($request->fullUrl()),
            'route_name' => $request->route()->getName(), 'opened_at' => $now->format('Y-m-d H:i:s.v'),
            'opened_at_utc' => $now->copy()->utc()->format('Y-m-d H:i:s.v'),
        ];
        $request->session()->put('activity.visit', $visitId);
        if (!$visit && TrafficFilter::minimumMilliseconds() > 0) {
            if (!$pending) $request->attributes->set('activity.pending_new_visit', true);
            return (new PendingVisits)->stage($request, $page, $visitRow);
        }
        $stored = DB::transaction(function () use ($visit, $visitRow, $page) {
            if (!PurgedUsers::lock($page['user_id'])) return false;
            if (!$visit) DB::table('activity_visits')->insert($visitRow);
            DB::table('activity_pages')->insert($page);
            return true;
        }, 3);
        return $stored ? (object) $page : null;
    }

    public function request(Request $request, $response, $page, $started)
    {
        if (ExcludedAccounts::request($request)) return;
        $html = stripos($response->headers->get('Content-Type', ''), 'text/html') !== false;
        $type = $request->isMethod('GET') && !$request->ajax() && $html && $response->isSuccessful() ? 'page_view' : 'request';
        $resourceType = null; $resourceId = null;
        foreach (['piece', 'playlist', 'folder', 'composer', 'performance'] as $name) {
            $resource = $request->route($name);
            if ($resource !== null) {
                $resourceType = $name;
                $resourceId = is_object($resource) && method_exists($resource, 'getKey') ? $resource->getKey() : $resource;
                break;
            }
        }
        $metadata = Metadata::select($request->all(), Metadata::QUERY_KEYS);
        $referrer = $request->headers->get('referer');
        if ($referrer && in_array(parse_url($referrer, PHP_URL_HOST), [config('app.short_url'), 'my.'.config('app.short_url')], true)) {
            $metadata['navigation'] = Metadata::url($referrer);
        }
        $metadata['response_ms'] = (int) round((microtime(true) - $started) * 1000);
        if ($request->attributes->has('activity.action')) {
            $action = $request->attributes->get('activity.action');
            $type = $action['type'];
            $metadata = array_merge($metadata, $action['metadata'] ?? []);
        }
        $event = [
            'type' => $type, 'source' => 'server', 'occurred_at' => now()->format('Y-m-d H:i:s.v'), 'occurred_at_utc' => now('UTC')->format('Y-m-d H:i:s.v'),
            'url' => Metadata::url($request->fullUrl()), 'route_name' => $request->route()->getName(),
            'method' => $request->method(), 'status' => $response->getStatusCode(),
            'resource_type' => $resourceType, 'resource_id' => $resourceId,
            'metadata' => $metadata,
        ];
        if (!empty($page->_pending) && $type === 'page_view') {
            (new PendingVisits)->opening($request, $page, $event, $response);
            return;
        }
        // Failed probes, redirects and bare requests do not establish a visit.
        // Keep them only within an already qualified visit (legacy opt-out retained).
        if ($type === 'request' && TrafficFilter::minimumMilliseconds() > 0
            && (!empty($page->_pending) || !PendingVisits::qualified($page->visit_id ?? $request->session()->get('activity.visit', '')))) return;
        $this->insert($request, $page, $event);
    }

    public function insert(Request $request, $page, array $event, $visitLocked = false)
    {
        if ($visitLocked) return $this->insertRow($request, $page, $event, true);
        return DB::transaction(function () use ($request, $page, $event) { return $this->insertRow($request, $page, $event, false); }, 3);
    }

    private function insertRow(Request $request, $page, array $event, $visitLocked)
    {
        if (ExcludedAccounts::request($request) || ExcludedAccounts::id($event['user_id'] ?? $request->attributes->get('activity.actor', auth('web')->id()))) return false;
        if (!PurgedUsers::lock($event['user_id'] ?? $request->attributes->get('activity.actor', auth('web')->id()))) return false;
        // Preserve successful account actions, but do not fabricate a short visit
        // just to attach them. They remain ungrouped account milestones.
        if (!empty($page->_pending)) $page = null;
        $metadata = $event['metadata'] ?? [];
        $visitor = $request->attributes->get('activity.visitor') ?: $this->visitor($request);
        $actor = $request->attributes->get('activity.actor', auth('web')->id());
        $visit = $page ? $page->visit_id : DB::table('activity_visits')->where('id', $request->session()->get('activity.visit', ''))
            ->where('visitor_id', $visitor)->where('user_id', $actor)->value('id');
        if ($visit && !$visitLocked && !DB::table('activity_visits')->where('id', $visit)->lockForUpdate()->first()) { $visit = null; $page = null; }
        $row = array_merge([
            'event_id' => (string) Str::uuid(), 'visit_id' => $visit,
            'page_id' => $page ? $page->id : null, 'user_id' => $actor,
            'visitor_id' => $visitor, 'platform' => $this->platform($request),
            'received_at' => now()->format('Y-m-d H:i:s.v'),
        ], $event);
        $row['metadata'] = json_encode($metadata, JSON_THROW_ON_ERROR);
        $row['occurred_at_utc'] = $row['occurred_at_utc'] ?? Carbon::parse($row['occurred_at'], config('app.timezone'))->utc()->format('Y-m-d H:i:s.v');
        $row['received_at_utc'] = now('UTC')->format('Y-m-d H:i:s.v');
        // Unique event IDs make browser retries safe. Other database errors must propagate.
        try {
            DB::table('activity_events')->insert($row);
        } catch (\Illuminate\Database\QueryException $exception) {
            if (!DB::table('activity_events')->where('event_id', $row['event_id'])->exists()) throw $exception;
            return false;
        }
        $activityUtc = min($row['occurred_at_utc'], $row['received_at_utc']);
        $activityAt = Carbon::parse($activityUtc, 'UTC')->timezone(config('app.timezone'))->format('Y-m-d H:i:s.v');
        if ($visit) DB::table('activity_visits')->where('id', $visit)->where(function ($query) use ($activityUtc, $activityAt) {
            $query->where('last_activity_at_utc', '<', $activityUtc)->orWhere(function ($legacy) use ($activityAt) { $legacy->whereNull('last_activity_at_utc')->where('last_activity_at', '<', $activityAt); });
        })->update(['last_activity_at' => $activityAt, 'last_activity_at_utc' => $activityUtc]);
        if ($visit && !in_array($row['type'], ['page_ready', 'page_checkpoint', 'page_hidden', 'page_visible', 'page_leave', 'page_restore'], true)) {
            DB::table('activity_visits')->where('id', $visit)->where(function ($query) use ($activityUtc) { $query->whereNull('last_interaction_at_utc')->orWhere('last_interaction_at_utc', '<', $activityUtc); })->update(['last_interaction_at' => $activityAt, 'last_interaction_at_utc' => $activityUtc]);
        }
        return true;
    }

    public function browser(Request $request, $page, array $events, array $qualification = [])
    {
        if (ExcludedAccounts::request($request) || ExcludedAccounts::id($page->user_id ?? null)) return true;
        if (!empty($page->_pending)) {
            $pending = new PendingVisits;
            $pages = $pending->observe($request, $page, $events, $qualification);
            if (!$pages) return false;
            try { DB::transaction(function () use ($request, $page, $pages) {
                if (!PurgedUsers::lock($page->user_id)) return;
                DB::table('activity_visits')->insertOrIgnore($page->_visit);
                foreach ($pages as $entry) {
                    $row = $entry['page']; unset($row['_pending'], $row['_visit']);
                    DB::table('activity_pages')->insertOrIgnore($row);
                    $stored = (object) $row;
                    $entry['opening']['metadata']['engagement_qualified'] = true;
                    $this->insert($request, $stored, $entry['opening'], true);
                    $this->browser($request, $stored, array_values($entry['events']));
                }
                $visit = DB::table('activity_visits')->where('id', $page->visit_id)->first();
                if (Time::date($visit, 'started_at')->diffInMilliseconds(Time::date($visit, 'last_activity_at')) < TrafficFilter::minimumMilliseconds()) throw new UnqualifiedVisit;
            }, 3); } catch (UnqualifiedVisit $exception) { return false; }
            $pending->forgetVisit($request, $page->visit_id);
            return true;
        }
        DB::transaction(function () use ($request, $page, $events) {
            if (!PurgedUsers::lock($page->user_id)) return;
            if (!DB::table('activity_visits')->where('id', $page->visit_id)->lockForUpdate()->first()) return;
            $remaining = max(0, (int) config('activity.max_browser_events_per_page', 2000) - DB::table('activity_events')->where('page_id', $page->id)->where('source', 'browser')->count());
            $events = array_slice($events, 0, $remaining);
            foreach ($events as $event) {
                $metadata = Metadata::select($event['metadata'] ?? [], Metadata::BROWSER_KEYS);
                if (isset($metadata['target'])) $metadata['target'] = Metadata::url($metadata['target']);
                if ($event['type'] === 'media_play' && !empty($metadata['playback_id'])) {
                    // The visit row is locked above, so concurrent batches for
                    // this owned page cannot insert the same playback start.
                    $start = DB::table('activity_events')->where('page_id', $page->id)->where('source', 'browser')
                        ->where('type', 'media_play')->where('metadata->playback_id', $metadata['playback_id'])->orderBy('id')->first();
                    if ($start) {
                        if ($start->event_id !== $event['id']) {
                            // Older clients emit another start after buffering.
                            // Retain the greatest measured totals without adding
                            // starts or summing cumulative observations twice.
                            $saved = json_decode($start->metadata ?: '{}', true) ?: [];
                            foreach (['played_seconds', 'unique_watched_seconds', 'unique_watched_percent'] as $key) {
                                if (isset($metadata[$key]) && is_numeric($metadata[$key])) $saved[$key] = max($saved[$key] ?? 0, $metadata[$key]);
                            }
                            if (!isset($saved['duration_seconds']) && isset($metadata['duration_seconds'])) $saved['duration_seconds'] = $metadata['duration_seconds'];
                            DB::table('activity_events')->where('id', $start->id)->update(['metadata' => json_encode($saved, JSON_THROW_ON_ERROR)]);
                        }
                        continue;
                    }
                }
                $occurred = !empty($page->opened_at_utc) ? Carbon::parse($page->opened_at_utc, 'UTC') : Carbon::parse($page->opened_at, config('app.timezone'));
                $occurred->addMilliseconds($event['elapsed_ms']);
                $inserted = $this->insert($request, $page, [
                    'event_id' => $event['id'], 'type' => $event['type'], 'source' => 'browser',
                    'occurred_at' => $occurred->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s.v'),
                    'occurred_at_utc' => $occurred->copy()->utc()->format('Y-m-d H:i:s.v'),
                    'elapsed_ms' => $event['elapsed_ms'], 'sequence' => $event['sequence'],
                    'url' => $page->url, 'route_name' => $page->route_name, 'metadata' => $metadata,
                    'resource_type' => isset($metadata['piece_id']) ? 'piece' : null, 'resource_id' => $metadata['piece_id'] ?? null,
                ], true);
                if (!$inserted) continue;
                $updates = ['elapsed_ms' => DB::raw('CASE WHEN elapsed_ms < '.(int) $event['elapsed_ms'].' THEN '.(int) $event['elapsed_ms'].' ELSE elapsed_ms END')];
                if (isset($metadata['visible_ms']) && is_numeric($metadata['visible_ms'])) {
                    $visible = min((int) $event['elapsed_ms'], max(0, (int) $metadata['visible_ms']));
                    $updates['visible_ms'] = DB::raw('CASE WHEN visible_ms < '.$visible.' THEN '.$visible.' ELSE visible_ms END');
                }
                DB::table('activity_pages')->where('id', $page->id)->update($updates);
            }
        }, 3);
        return true;
    }
}
