<?php

namespace App\Console\Commands;

use App\Analytics\GoogleAnalyticsConversions;
use App\Services\CampaignConversions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendGoogleAnalyticsConversions extends Command
{
    protected $signature = 'analytics:send-conversions {--status : Show configuration and delivery counts without sending}';
    protected $description = 'Deliver consented, verified web membership trials and first payments to Google Analytics';

    public function handle(GoogleAnalyticsConversions $google, CampaignConversions $campaigns)
    {
        if ($this->option('status')) {
            $this->line('Google Analytics delivery: '.($google->configured() ? 'configured' : 'not configured'));
            $rows = DB::table('google_analytics_conversions')->select('state')->selectRaw('COUNT(*) as total')->groupBy('state')->orderBy('state')->get();
            $this->table(['State', 'Count'], $rows->map(function ($row) { return [$row->state, $row->total]; })->all());
            return 0;
        }
        if (!app()->environment('production') || !config('attribution.conversion_links_enabled')) return 0;
        // Reconciliation is read-only toward Stripe and keeps working when the
        // checkout tab never returns. Delivery never runs inside a billing request.
        $campaigns->reconcile();
        $this->line('Submitted '.$google->sendPending().' conversion events');
        return 0;
    }
}
