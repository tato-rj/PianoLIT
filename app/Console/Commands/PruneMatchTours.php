<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneMatchTours extends Command
{
    protected $signature = 'api-v2:prune-match-tours';
    protected $description = 'Delete native tour state and retry receipts one day after expiration';

    public function handle()
    {
        $deleted = 0;
        do {
            $ids = DB::table('api_v2_match_tours')->where('expires_at', '<=', now()->subDay())->orderBy('id')->limit(500)->pluck('id');
            $deleted += DB::table('api_v2_match_tours')->whereIn('id', $ids)->delete();
        } while ($ids->count() === 500);
        $this->info('Removed '.$deleted.' expired native tours.');
        return 0;
    }
}
