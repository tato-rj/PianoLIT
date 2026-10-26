<?php

namespace App\Console\Commands;

use App\Admin;
use App\Mail\Admin\AdminReport;
use App\Services\Admin\WeeklySnapshot;
use Illuminate\Console\Command;

class SendAdminReport extends Command
{
    protected $signature = 'pianolit:admin-report';

    protected $description = 'Queue the weekly growth, engagement and membership snapshot for managers.';

    public function generateReports()
    {
        return app(WeeklySnapshot::class)->build();
    }

    public function handle()
    {
        if (!app(\App\Mail\AutomaticEmailSettings::class)->enabled('admin-weekly-snapshot')) {
            $this->info('Weekly admin snapshot sending is off.');
            return 0;
        }
        // Freeze one aggregate payload for all recipients and delayed queue jobs.
        $reports = $this->generateReports();
        foreach (Admin::managers()->get() as $recipient) {
            \Mail::to($recipient->email)->queue(new AdminReport($reports, $recipient));
        }
        $this->info('The weekly snapshot emails were queued successfully.');
        return 0;
    }
}
