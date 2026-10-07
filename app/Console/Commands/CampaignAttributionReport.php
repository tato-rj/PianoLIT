<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CampaignAttributionReport as Report;

class CampaignAttributionReport extends Command
{
    protected $signature = 'attribution:report {--since= : Arrival cohort start date, YYYY-MM-DD}';
    protected $description = 'Read consented video arrivals, web trials and first successful subscription payments';

    public function handle(Report $report)
    {
        if (! config('attribution.enabled')) { $this->warn('Campaign attribution is disabled.'); return 0; }
        $since = $this->option('since') ?: now()->subDays(30)->toDateString();
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $since) || ! checkdate((int) substr($since, 5, 2), (int) substr($since, 8, 2), (int) substr($since, 0, 4))) {
            $this->error('Use a valid YYYY-MM-DD date.'); return 1;
        }
        $this->line('Consented arrivals since '.$since.'; conversions belong to that arrival cohort.');
        $this->table(['Campaign', 'Source', 'Placement', 'Arrivals', 'Trials', 'First paid subscriptions', 'First payment revenue'], $report->rows($since));
        $this->line('Not all YouTube clicks; excludes denied/unmeasured visits. Revenue excludes renewals and is gross, before fees/refunds.');
        return 0;
    }
}
