<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneApiV2Sessions extends Command
{
    protected $signature = 'api-v2:prune-sessions';
    protected $description = 'Delete expired v2 authentication sessions';

    public function handle()
    {
        $deleted = 0;
        do {
            $ids = DB::table('api_v2_sessions')->where('expires_at', '<=', now('UTC')->toDateTimeString())->orderBy('id')->limit(500)->pluck('id');
            if ($ids->isNotEmpty()) $deleted += DB::table('api_v2_sessions')->whereIn('id', $ids)->delete();
        } while ($ids->count() === 500);
        $this->info('Expired v2 sessions deleted: '.$deleted);
        return 0;
    }
}
