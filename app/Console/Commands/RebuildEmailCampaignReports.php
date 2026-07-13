<?php

namespace App\Console\Commands;

use App\EmailCampaignReport;
use App\EmailLog;
use Illuminate\Console\Command;

class RebuildEmailCampaignReports extends Command
{
    protected $signature = 'email-reports:rebuild';
    protected $description = 'Rebuild the email campaign summaries used by the admin reports table.';

    public function handle()
    {
        EmailCampaignReport::truncate();

        $reports = EmailLog::generate()->get();

        foreach ($reports as $report) {
            EmailCampaignReport::create([
                'list_id' => $report->list_id,
                'name' => $report->name,
                'sent_at' => $report->sent_at,
                'emails_count' => $report->emails_count,
                'delivered_count' => $report->delivered_count,
                'failed_count' => $report->failed_count,
                'opens_count' => $report->opens_count,
                'clicks_count' => $report->clicks_count,
            ]);
        }

        $this->info('Rebuilt '.$reports->count().' email campaign reports.');

        return 0;
    }
}
