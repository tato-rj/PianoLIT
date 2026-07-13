<?php

namespace App\Log;

use Illuminate\Support\Facades\Redis;

class UserLogIndex
{
    public function countsKey()
    {
        return config('database.redis.prefix').'admin:user-logs:counts';
    }

    public function lastActiveKey()
    {
        return config('database.redis.prefix').'admin:user-logs:last-active';
    }

    public function totalKey()
    {
        return config('database.redis.prefix').'admin:user-logs:total';
    }

    public function readyKey()
    {
        return config('database.redis.prefix').'admin:user-logs:ready';
    }

    public function record(string $logKey, int $timestamp)
    {
        if (! preg_match('/^user:(\d+):(app|web|webapp)$/', $logKey, $matches)) {
            return;
        }

        $userId = $matches[1];

        Redis::pipeline(function ($pipe) use ($userId, $timestamp) {
            $pipe->zincrby($this->countsKey(), 1, $userId);
            $pipe->zadd($this->lastActiveKey(), $timestamp, $userId);
            $pipe->incr($this->totalKey());
        });
    }

    public function initialize($userId)
    {
        Redis::zadd($this->countsKey(), 'NX', 0, (string) $userId);
        Redis::zadd($this->lastActiveKey(), 'NX', 0, (string) $userId);
    }

    public function remove($userId)
    {
        $count = $this->count($userId);

        Redis::zrem($this->countsKey(), (string) $userId);
        Redis::zrem($this->lastActiveKey(), (string) $userId);

        if ($count) {
            Redis::decrby($this->totalKey(), $count);
        }
    }

    public function count($userId)
    {
        return (int) (Redis::zscore($this->countsKey(), (string) $userId) ?: 0);
    }

    public function lastActiveTimestamp($userId)
    {
        return (int) (Redis::zscore($this->lastActiveKey(), (string) $userId) ?: 0);
    }

    public function total()
    {
        return (int) (Redis::get($this->totalKey()) ?: 0);
    }

    public function isReady()
    {
        return (bool) Redis::get($this->readyKey());
    }

    public function rankedIds(string $field, int $start, int $length, string $direction = 'desc')
    {
        $key = $field === 'visits' ? $this->countsKey() : $this->lastActiveKey();
        $end = $start + $length - 1;

        return $direction === 'asc'
            ? Redis::zrange($key, $start, $end)
            : Redis::zrevrange($key, $start, $end);
    }

    public function score(string $field, $userId)
    {
        return $field === 'visits'
            ? $this->count($userId)
            : $this->lastActiveTimestamp($userId);
    }

    public function statsFor(array $userIds)
    {
        $scores = Redis::pipeline(function ($pipe) use ($userIds) {
            foreach ($userIds as $userId) {
                $pipe->zscore($this->countsKey(), (string) $userId);
                $pipe->zscore($this->lastActiveKey(), (string) $userId);
            }
        });
        $stats = [];

        foreach ($userIds as $position => $userId) {
            $stats[$userId] = [
                'visits' => (int) ($scores[$position * 2] ?: 0),
                'last_active' => (int) ($scores[$position * 2 + 1] ?: 0),
            ];
        }

        return $stats;
    }
}
