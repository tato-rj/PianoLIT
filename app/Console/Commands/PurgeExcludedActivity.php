<?php

namespace App\Console\Commands;

use App\Activity\{ExcludedAccounts, ExcludedHistory};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Redis};

class PurgeExcludedActivity extends Command
{
    protected $signature = 'activity:purge-excluded
        {--execute : Delete only the configured excluded accounts\' tracking records}
        {--legacy-redis : Also inspect/remove their exact legacy Redis activity hashes}';
    protected $description = 'Preview or remove excluded accounts\' analytics history without deleting accounts, saved work or billing records.';

    public function handle()
    {
        try { return $this->cleanup(); }
        catch (\Throwable $exception) {
            // A maintenance failure must not send account details to an error
            // tracking service. Completed batches can be safely retried.
            $this->error('Cleanup stopped: '.$exception->getMessage());
            return 1;
        }
    }

    private function cleanup()
    {
        $history = new ExcludedHistory;
        $connection = DB::connection();
        $this->line('Database: '.$connection->getDriverName().' / '.$connection->getConfig('host').' / '.$connection->getDatabaseName());
        $this->line('Excluded emails: '.implode(', ', ExcludedAccounts::emails()));
        $counts = $history->counts();
        $this->info($this->option('execute') ? 'Removing configured excluded accounts\' tracking records.' : 'PREVIEW ONLY — no writes.');
        $this->table(array_keys($counts), [array_values($counts)]);
        $keys = [];
        if ($this->option('legacy-redis')) {
            $redis = Redis::connection('default');
            foreach (ExcludedAccounts::ids()->pluck('id') as $id) foreach (['app', 'web', 'webapp'] as $platform) {
                foreach (array_unique(['user:'.$id.':'.$platform, config('database.redis.prefix', '').'user:'.$id.':'.$platform]) as $key) {
                    if (!$redis->exists($key)) continue;
                    if (!in_array((string) $redis->type($key), ['hash', '5'], true)) {
                        $this->error('An expected legacy activity key is not a hash. No cleanup was applied.'); return 1;
                    }
                    $keys[] = $key;
                }
            }
            $this->line('Matching legacy activity hashes: '.count($keys));
        }
        if ($this->option('execute')) {
            $history->purge();
            foreach ($keys as $key) $redis->del($key);
            $remaining = $history->counts(); unset($remaining['accounts']);
            if (array_sum($remaining)) { $this->error('Matching tracking rows remain; retry this bounded cleanup.'); return 1; }
            $this->info('Excluded tracking history removed. Accounts, favorites, markings and billing records are preserved.');
        }
        return 0;
    }
}
