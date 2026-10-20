<?php

namespace App\Console\Commands;

use App\EmailLog;
use App\Services\EmailCampaignReports;
use Illuminate\Console\Command;

class RebuildEmailCampaignReports extends Command
{
    protected $signature = 'email-reports:rebuild {--campaign=* : Rebuild only these exact campaign IDs} {--dry-run}';
    protected $description = 'Rebuild the email campaign summaries used by the admin reports table.';

    public function handle(EmailCampaignReports $rebuilder)
    {
        $ids = $this->option('campaign') ?: EmailLog::whereNotNull('list_id')->distinct()->pluck('list_id')->all();
        $count = 0;
        foreach ($ids as $id) {
            if ($rebuilder->rebuild($id, (bool) $this->option('dry-run')) !== null) $count++;
        }

        $this->info(($this->option('dry-run') ? 'Would rebuild ' : 'Rebuilt ').$count.' email campaign reports; historical snapshots without source rows were retained.');

        return 0;
    }
}
