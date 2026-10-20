<?php

namespace App\Console\Commands;

use App\Services\FreePickSchedule;
use App\Services\FreePickRelease;
use Illuminate\Console\Command;

class SyncFreePickHistory extends Command
{
    protected $signature = 'freepicks:sync-history';
    protected $description = 'Activate due Freepicks, refresh Discover and queue requested emails';

    public function handle(FreePickSchedule $schedule, FreePickRelease $release)
    {
        $this->info('Synchronized '.$schedule->syncHistory().' Freepick dates.');
        $this->info('Completed '.$release->processDue().' Freepick releases.');
        return 0;
    }
}
