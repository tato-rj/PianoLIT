<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateEmailLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp:update-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach (\App\EmailLog::all() as $log) {
            if ($log->delivered_at)
                $log->update(['unique_delivered' => 1]);
            if ($log->failed_at)
                $log->update(['unique_failed' => 1]);
            if ($log->opened)
                $log->update(['unique_opened' => 1]);
            if ($log->clicked)
                $log->update(['unique_clicked' => 1]);
        }

        $this->info('The logs have been updated.');
    }
}
