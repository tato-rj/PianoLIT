<?php

namespace App\Console\Commands;

use App\Activity\{NoisePruner, TrafficFilter};
use Illuminate\Console\Command;

class PruneActivityNoise extends Command
{
    protected $signature = 'activity:prune-noise
        {--execute : Delete matching history; otherwise preview without writes}
        {--older-than=60 : Minimum minutes since the last recorded activity (at least 30)}
        {--batch=200 : Visits per transaction (1-1000)}
        {--limit=10000 : Maximum matching visits per run (1-1000000)}';

    protected $description = 'Preview or remove old web visits below the configured engagement minimum, preserving confirmed account outcomes.';

    public function handle()
    {
        $options = [];
        foreach (['older-than' => [30, 525600], 'batch' => [1, 1000], 'limit' => [1, 1000000]] as $name => $bounds) {
            $value = filter_var($this->option($name), FILTER_VALIDATE_INT);
            if ($value === false || $value < $bounds[0] || $value > $bounds[1]) { $this->error('Invalid --'.$name.'; expected '.$bounds[0].'-'.$bounds[1].'.'); return 1; }
            $options[$name] = $value;
        }
        $minimum = TrafficFilter::minimumMilliseconds();
        if (!$minimum) { $this->error('The minimum visit duration is disabled; nothing will be pruned.'); return 1; }
        $execute = (bool) $this->option('execute');
        $this->info($execute ? 'Deleting old, short web visits.' : 'PREVIEW ONLY — no database writes.');
        $this->line('Threshold: less than '.($minimum / 1000).' seconds; last activity older than '.$options['older-than'].' minutes.');
        $counts = (new NoisePruner)->run(now('UTC')->subMinutes($options['older-than']), $minimum, $execute, $options['batch'], $options['limit']);
        $this->table(['Visits', 'Pages', 'Events', 'Identity links', 'Confirmed/imported/app events preserved'], [array_values($counts)]);
        $this->line('Mobile history, legacy import checkpoints/counters, and account/saved-music tables are untouched.');
        if (!$execute) $this->line('Run again with --execute to apply this cleanup.');
        if ($counts['visits'] === $options['limit']) $this->line('Run limit reached; repeat to continue in bounded batches.');
        return 0;
    }
}
