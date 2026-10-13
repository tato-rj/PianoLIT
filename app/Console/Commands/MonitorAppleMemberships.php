<?php

namespace App\Console\Commands;

use App\Services\Apple\MembershipMonitor;
use Illuminate\Console\Command;

class MonitorAppleMemberships extends Command
{
    protected $signature = 'memberships:sync-apple {--limit=200 : Maximum receipts to check (1-200)}';
    protected $description = 'Refresh admin Apple membership evidence without changing mobile billing records';

    public function handle(MembershipMonitor $monitor)
    {
        if (! config('apple-membership-monitor.enabled') || ! app()->environment(['production', 'testing'])) {
            $this->info('Apple membership monitoring is disabled or this is not production.');
            return 0;
        }
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 200]]);
        if ($limit === false) {
            $this->error('The limit must be between 1 and 200.');
            return 1;
        }
        $result = $monitor->run($limit);
        if ($result === null) {
            $this->info('An Apple membership check is already running.');
            return 0;
        }
        $this->info("Checked {$result['checked']}; verified {$result['verified']}; needs review {$result['failed']}.");
        return $result['failed'] ? 1 : 0;
    }
}
