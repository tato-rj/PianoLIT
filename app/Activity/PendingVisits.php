<?php

namespace App\Activity;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Crypt, DB};

/** Signed provisional context only; raw unqualified events stay in the browser. */
class PendingVisits
{
    const KEY = 'activity.pending_visit';
    const PREFIX = 'activity-pending-v2:';

    public static function qualified($visitId)
    {
        return DB::table('activity_events')->where('visit_id', $visitId)->where('source', 'server')->where('type', 'page_view')->where('metadata->engagement_qualified', true)->exists();
    }

    public function find(Request $request, $value)
    {
        if (strpos($value, self::PREFIX) !== 0) return null;
        $data = json_decode(substr($value, strlen(self::PREFIX)), true);
        if (!is_array($data) || !isset($data['page'], $data['start'], $data['opening'])) return null;
        $page = (object) $data['page'];
        if ($page->visitor_id !== $request->session()->get('activity.visitor') || $page->user_id !== auth('web')->id()
            || $page->platform !== (new Recorder)->platform($request)
            || Time::date($page, 'opened_at')->lt(now('UTC')->subHours(config('activity.page_token_hours')))) return null;
        $start = \Carbon\Carbon::parse($data['start'], 'UTC');
        $local = $start->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s.v');
        $page->_visit = ['id' => $page->visit_id, 'user_id' => $page->user_id, 'visitor_id' => $page->visitor_id, 'platform' => $page->platform,
            'started_at' => $local, 'last_activity_at' => $local, 'last_interaction_at' => $local,
            'started_at_utc' => $data['start'], 'last_activity_at_utc' => $data['start'], 'last_interaction_at_utc' => $data['start']];
        $page->_opening = $data['opening']; $page->_pending = true;
        return $page;
    }

    public function visit(Request $request, $id, $visitor, $user, $platform)
    {
        $row = $request->session()->get(self::KEY);
        return $row && $row['id'] === $id && $row['visitor_id'] === $visitor && $row['user_id'] === $user && $row['platform'] === $platform
            && Time::date((object) $row, 'last_interaction_at')->gte(now('UTC')->subMinutes(config('activity.visit_idle_minutes'))) ? $row : null;
    }

    public function stage(Request $request, array $page, array $visit)
    {
        // Session contains only the current visit identity/clock, never URLs,
        // browsing events or a second copy of SQL activity history.
        $visit['last_interaction_at'] = $page['opened_at']; $visit['last_interaction_at_utc'] = $page['opened_at_utc'];
        $request->session()->put(self::KEY, $visit);
        $page['_pending'] = true; $page['_visit'] = $visit;
        return (object) $page;
    }

    public static function placeholder($page)
    {
        return 'ACTIVITY_PENDING_TOKEN_'.$page->id;
    }

    public function opening(Request $request, $page, array $event, $response)
    {
        $row = (array) $page; unset($row['_pending'], $row['_visit']);
        $row['url'] = mb_strcut($row['url'], 0, 1000, 'UTF-8');
        $event['event_id'] = (string) \Ramsey\Uuid\Uuid::uuid5(\Ramsey\Uuid\Uuid::NAMESPACE_URL, 'pianolit:page:'.$page->id);
        unset($event['url'], $event['route_name']);
        if (strlen(json_encode($event['metadata'])) > 512) $event['metadata'] = [];
        $payload = ['page' => $row, 'start' => $page->_visit['started_at_utc'], 'opening' => $event];
        $token = Crypt::encryptString(self::PREFIX.json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        if (strlen($token) > 4096) {
            $payload['page']['url'] = mb_strcut($row['url'], 0, 500, 'UTF-8'); $payload['opening']['metadata'] = [];
            $token = Crypt::encryptString(self::PREFIX.json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
        $response->headers->remove('Content-Length');
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->setContent(str_replace(self::placeholder($page), $token, $response->getContent()));
    }

    public function observe(Request $request, $page, array $events, array $qualification = [])
    {
        $entries = [];
        foreach (array_merge([['page' => $page, 'events' => $events]], $qualification) as $part) {
            $p = $part['page'];
            if (!isset($entries[$p->id])) {
                $opening = $p->_opening ?? null;
                // A qualifying page may already have been stored by an earlier batch.
                if (!$opening) continue;
                $opening['url'] = $p->url; $opening['route_name'] = $p->route_name;
                $row = (array) $p; unset($row['_opening']);
                $entries[$p->id] = ['page' => $row, 'opening' => $opening, 'events' => [], 'visible_ms' => 0, 'played_ms' => 0];
            }
            foreach ($part['events'] as $event) {
                if (isset($entries[$p->id]['events'][$event['id']])) continue;
                $entries[$p->id]['events'][$event['id']] = $event;
                $entries[$p->id]['visible_ms'] = max($entries[$p->id]['visible_ms'], min($event['elapsed_ms'], (int) ($event['metadata']['visible_ms'] ?? 0)));
                if (strpos($event['type'], 'media_') === 0) $entries[$p->id]['played_ms'] = max($entries[$p->id]['played_ms'], min($event['elapsed_ms'], (int) (($event['metadata']['played_seconds'] ?? 0) * 1000)));
            }
        }
        $owned = DB::table('activity_visits')->where('id', $page->visit_id)->where('visitor_id', $page->visitor_id)->where('user_id', $page->user_id)->where('platform', $page->platform)->exists() && self::qualified($page->visit_id);
        if ($owned) return $entries;
        $minimum = TrafficFilter::minimumMilliseconds();
        $engagement = max(array_sum(array_column($entries, 'visible_ms')), $entries ? max(array_column($entries, 'played_ms')) : 0);
        $start = Time::date((object) $page->_visit, 'started_at');
        return $engagement >= $minimum && $start->lte(now('UTC')->subMilliseconds($minimum)) ? $entries : [];
    }

    public function forgetVisit(Request $request, $visitId)
    {
        if (($request->session()->get(self::KEY)['id'] ?? null) === $visitId) $request->session()->forget(self::KEY);
    }
}
