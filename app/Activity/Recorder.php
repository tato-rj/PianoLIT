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
            && !auth('admin')->check()
            && $request->getHost() !== 'admin.'.config('app.short_url')
            && !$request->routeIs('activity.*', 'admin.*', 'api.*');
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
        if (!is_string($token) || strlen($token) > 4096) return null;
        try {
            $id = Crypt::decryptString($token);
            return DB::table('activity_pages')->where('id', $id)
                ->where('visitor_id', $request->session()->get('activity.visitor', ''))
                ->where('user_id', auth('web')->id())
                ->where('platform', $this->platform($request))
                ->where('opened_at', '>=', now()->subHours(config('activity.page_token_hours')))->first();
        } catch (\Illuminate\Contracts\Encryption\DecryptException $exception) {
            return null;
        }
    }

    public function openPage(Request $request)
    {
        $visitor = $request->attributes->get('activity.visitor') ?: $this->visitor($request);
        $user = auth('web')->id();
        $platform = $this->platform($request);
        $now = now();
        $visit = DB::table('activity_visits')->where('id', $request->session()->get('activity.visit', ''))
            ->where('visitor_id', $visitor)->where('user_id', $user)
            ->where('last_activity_at', '>=', $now->copy()->subMinutes(config('activity.visit_idle_minutes')))->first();
        $visitId = $visit ? $visit->id : (string) Str::uuid();
        $page = [
            'id' => (string) Str::uuid(), 'visit_id' => $visitId, 'user_id' => $user, 'visitor_id' => $visitor,
            'platform' => $platform, 'url' => Metadata::url($request->fullUrl()),
            'route_name' => $request->route()->getName(), 'opened_at' => $now->format('Y-m-d H:i:s.v'),
        ];
        DB::transaction(function () use ($visit, $visitId, $user, $visitor, $platform, $now, $page) {
            if (!$visit) DB::table('activity_visits')->insert([
                'id' => $visitId, 'user_id' => $user, 'visitor_id' => $visitor, 'platform' => $platform,
                'started_at' => $now->format('Y-m-d H:i:s.v'), 'last_activity_at' => $now->format('Y-m-d H:i:s.v'),
            ]);
            DB::table('activity_pages')->insert($page);
        });
        $request->session()->put('activity.visit', $visitId);
        return (object) $page;
    }

    public function request(Request $request, $response, $page, $started)
    {
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
        $metadata['response_ms'] = (int) round((microtime(true) - $started) * 1000);
        if ($request->attributes->has('activity.action')) {
            $action = $request->attributes->get('activity.action');
            $type = $action['type'];
            $metadata = array_merge($metadata, $action['metadata'] ?? []);
        }
        $this->insert($request, $page, [
            'type' => $type, 'source' => 'server', 'occurred_at' => now()->format('Y-m-d H:i:s.v'),
            'url' => Metadata::url($request->fullUrl()), 'route_name' => $request->route()->getName(),
            'method' => $request->method(), 'status' => $response->getStatusCode(),
            'resource_type' => $resourceType, 'resource_id' => $resourceId,
            'metadata' => $metadata,
        ]);
    }

    public function insert(Request $request, $page, array $event)
    {
        $metadata = $event['metadata'] ?? [];
        $visitor = $request->attributes->get('activity.visitor') ?: $this->visitor($request);
        $actor = $request->attributes->get('activity.actor', auth('web')->id());
        $visit = $page ? $page->visit_id : DB::table('activity_visits')->where('id', $request->session()->get('activity.visit', ''))
            ->where('visitor_id', $visitor)->where('user_id', $actor)->value('id');
        $row = array_merge([
            'event_id' => (string) Str::uuid(), 'visit_id' => $visit,
            'page_id' => $page ? $page->id : null, 'user_id' => $actor,
            'visitor_id' => $visitor, 'platform' => $this->platform($request),
            'received_at' => now()->format('Y-m-d H:i:s.v'),
        ], $event);
        $row['metadata'] = json_encode($metadata, JSON_THROW_ON_ERROR);
        // Unique event IDs make browser retries safe. Other database errors must propagate.
        try {
            DB::table('activity_events')->insert($row);
        } catch (\Illuminate\Database\QueryException $exception) {
            if (!DB::table('activity_events')->where('event_id', $row['event_id'])->exists()) throw $exception;
        }
        $activityAt = min($row['occurred_at'], $row['received_at']);
        if ($visit) DB::table('activity_visits')->where('id', $visit)
            ->where('last_activity_at', '<', $activityAt)->update(['last_activity_at' => $activityAt]);
    }

    public function browser(Request $request, $page, array $events)
    {
        DB::transaction(function () use ($request, $page, $events) {
            foreach ($events as $event) {
                $metadata = Metadata::select($event['metadata'] ?? [], Metadata::BROWSER_KEYS);
                if (isset($metadata['target'])) $metadata['target'] = Metadata::url($metadata['target']);
                $this->insert($request, $page, [
                    'event_id' => $event['id'], 'type' => $event['type'], 'source' => 'browser',
                    'occurred_at' => Carbon::parse($page->opened_at)->addMilliseconds($event['elapsed_ms'])->format('Y-m-d H:i:s.v'),
                    'elapsed_ms' => $event['elapsed_ms'], 'sequence' => $event['sequence'],
                    'url' => $page->url, 'route_name' => $page->route_name, 'metadata' => $metadata,
                ]);
                $updates = ['elapsed_ms' => DB::raw('CASE WHEN elapsed_ms < '.(int) $event['elapsed_ms'].' THEN '.(int) $event['elapsed_ms'].' ELSE elapsed_ms END')];
                if (isset($metadata['visible_ms']) && is_numeric($metadata['visible_ms'])) {
                    $visible = min((int) $event['elapsed_ms'], max(0, (int) $metadata['visible_ms']));
                    $updates['visible_ms'] = DB::raw('CASE WHEN visible_ms < '.$visible.' THEN '.$visible.' ELSE visible_ms END');
                }
                DB::table('activity_pages')->where('id', $page->id)->update($updates);
            }
        });
    }
}
