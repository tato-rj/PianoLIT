<?php

namespace App\Console\Commands;

use App\Services\FreePickSchedule;
use Illuminate\Console\Command;

class SyncFreePickHistory extends Command
{
    protected $signature = 'freepicks:sync-history';
    protected $description = 'Synchronize due Freepick dates for legacy highlight history';

    public function handle(FreePickSchedule $schedule)
    {
        $this->info('Synchronized '.$schedule->syncHistory().' Freepick dates.');
        return 0;
    }
}
