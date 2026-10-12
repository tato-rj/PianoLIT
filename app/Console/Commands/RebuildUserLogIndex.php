<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RebuildUserLogIndex extends Command
{
    protected $signature = 'redis:rebuild-user-log-index';
    protected $description = 'Retired: activity counts and last-active dates are queried directly from SQL.';

    public function handle()
    {
        $this->info('No Redis index is needed. Activity counts and last-active dates now use SQL. Import old history with activity:import-redis --verify.');
        return 0;
    }
}
