<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshDailyLogsCount extends Command
{
    protected $signature = 'redis:refresh-daily-logs';
    protected $description = 'Retired: daily activity statistics are queried directly from SQL.';

    public function handle()
    {
        $this->info('No Redis counters are needed. Daily activity statistics now use SQL. Import old history with activity:import-redis --verify.');
        return 0;
    }
}
