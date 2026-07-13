<?php

namespace App\Console\Commands;

use App\Log\UserLogIndex;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class RebuildUserLogIndex extends Command
{
    protected $signature = 'redis:rebuild-user-log-index';
    protected $description = 'Build the compact visit-count and last-active indexes used by the admin logs table.';

    public function handle()
    {
        $index = new UserLogIndex;
        Redis::del($index->countsKey(), $index->lastActiveKey(), $index->totalKey(), $index->readyKey());

        User::select('id')->chunkById(1000, function ($users) use ($index) {
            foreach ($users as $user) {
                $index->initialize($user->id);
            }
        });

        $prefix = config('database.redis.prefix');
        $cursor = '0';
        $total = 0;

        do {
            [$cursor, $keys] = Redis::scan($cursor, 'MATCH', $prefix.'user:*', 'COUNT', 500);

            foreach ($keys as $key) {
                if (! preg_match('/^'.preg_quote($prefix, '/').'user:(\d+):(app|web|webapp)$/', $key, $matches)) {
                    continue;
                }

                $count = (int) Redis::hlen($key);
                if (! $count) {
                    continue;
                }

                $userId = $matches[1];
                if (Redis::zscore($index->countsKey(), $userId) === null) {
                    continue;
                }

                Redis::zincrby($index->countsKey(), $count, $userId);
                $timestamps = Redis::hkeys($key);
                $lastActive = $timestamps ? max(array_map('intval', $timestamps)) : 0;

                if ($lastActive > $index->lastActiveTimestamp($userId)) {
                    Redis::zadd($index->lastActiveKey(), $lastActive, $userId);
                }

                $total += $count;
            }
        } while ($cursor !== '0' && $cursor !== 0);

        Redis::set($index->totalKey(), $total);
        Redis::set($index->readyKey(), 1);

        $this->info("Indexed {$total} visits.");

        return 0;
    }
}
