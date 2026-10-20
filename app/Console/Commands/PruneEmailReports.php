<?php

namespace App\Console\Commands;

use App\Services\EmailReportRetention;
use Illuminate\Console\Command;

class PruneEmailReports extends Command
{
    protected $signature = 'email-reports:prune
        {--execute : Delete expired email history; otherwise preview without writes}
        {--batch=500 : Recipient rows per transaction (1-1000)}';

    protected $description = 'Remove email reports and tracking history for blasts sent more than three calendar months ago.';

    public function handle(EmailReportRetention $retention)
    {
        $batch = filter_var($this->option('batch'), FILTER_VALIDATE_INT);
        if ($batch === false || $batch < 1 || $batch > 1000) {
            $this->error('Invalid --batch; expected 1-1000.');
            return 1;
        }

        $execute = (bool) $this->option('execute');
        $cutoff = now()->subMonthsNoOverflow(3);
        $this->info($execute ? 'Deleting expired email history.' : 'PREVIEW ONLY — no database writes.');
        $this->line('Campaigns sent before '.$cutoff->toDateTimeString().' ('.config('app.timezone').').');
        $counts = $retention->run($cutoff, $execute, $batch);
        $this->table(['Reports', 'Recipient logs', 'Event receipts'], [array_values($counts)]);
        if (! $execute) $this->line('Run again with --execute to apply this cleanup.');

        return 0;
    }
}
