<?php

namespace App\Log;

use App\Activity\Metadata;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Keep the existing account helpers and mobile logger entry point on SQL.
class LogFactory
{
    protected $types = ['app', 'web', 'webapp'];

    public function push(Logger $logger)
    {
        if (!config('activity.enabled')) return;
        if (!preg_match('/^user:([1-9]\d*):(app|web|webapp)$/', $logger->getKey(), $matches) || (float) $matches[1] > 4294967295) return;
        if (\App\Activity\ExcludedAccounts::id($matches[1])) return;

        $request = request();
        $payload = json_decode($logger->getData(), true) ?: [];
        $metadata = ['data' => Metadata::select($payload['data'] ?? [], array_merge(Metadata::QUERY_KEYS, ['piece_id', 'playlist_id', 'tag_id']))];
        if ($matches[2] === 'app') {
            // The existing app supplies user_id without authenticated identity.
            // Preserve that association for analytics, never for authorization.
            $metadata['identity_source'] = 'legacy_user_id';
            $metadata['identity_verified'] = false;
            $metadata['visit_unknown'] = true;
        }
        $now = now()->format('Y-m-d H:i:s.v');
        $row = [
            'event_id' => (string) Str::uuid(), 'user_id' => (int) $matches[1],
            'platform' => $matches[2], 'type' => 'request', 'source' => 'server',
            'occurred_at' => $now, 'received_at' => $now,
            'occurred_at_utc' => now('UTC')->format('Y-m-d H:i:s.v'), 'received_at_utc' => now('UTC')->format('Y-m-d H:i:s.v'),
            'url' => Metadata::url($payload['url'] ?? $request->url()),
            'route_name' => $request->route() ? $request->route()->getName() : null,
            'method' => $request->method(), 'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
        ];
        DB::transaction(function () use ($row) {
            if (\App\Activity\PurgedUsers::lock($row['user_id'])) DB::table('activity_events')->insert($row);
        }, 3);
    }

    public function get($userId, $type = null)
    {
        if ($type) {
            return $this->query($userId, $type)->orderByDesc('occurred_at')->orderByDesc('id')->get()->mapWithKeys(function ($event) {
                $metadata = json_decode($event->metadata ?: '{}', true);
                return [$event->id => (object) [
                    'url' => $event->url, 'data' => $metadata['data'] ?? $metadata,
                    'occurred_at' => $event->occurred_at,
                ]];
            })->all();
        }
        $log = collect();
        foreach ($this->types as $platform) $log->$platform = $this->get($userId, $platform);
        return $log;
    }

    public function count($userId, $type = null)
    {
        return $this->query($userId, $type)->count();
    }

    public function last($userId, $type = null)
    {
        $date = $this->query($userId, $type)->max('occurred_at');
        return $date ? carbon($date) : null;
    }

    public function total($type = 'user')
    {
        $query = \App\Activity\ExcludedAccounts::events(DB::table('activity_events'))->whereNotNull('user_id');
        if ($type !== 'user') $query->where('platform', $this->platform($type));
        return $query->count();
    }

    private function query($userId, $type)
    {
        $query = \App\Activity\ExcludedAccounts::events(DB::table('activity_events'))->where('user_id', $userId);
        if ($type) $query->where('platform', $this->platform($type));
        return $query;
    }

    private function platform($type)
    {
        if (!in_array($type, $this->types, true)) throw new \InvalidArgumentException('Unknown activity platform.');
        return $type;
    }
}
