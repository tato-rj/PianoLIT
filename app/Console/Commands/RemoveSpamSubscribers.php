<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\{EmailLog, User, Subscription};

class RemoveSpamSubscribers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:remove-spam';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all subscriptions that have no user and failed in the last email report';

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
     * @return int
     */
    public function handle()
    {
        $query = EmailLog::whereNotNull('failed_at');

        $report = $query->take(20)->get();

        $report->each(function($spam) {
            if (User::byEmail($spam->recipient)->exists())
                $this->info('Delete ' . $spam->recipient);
        });

        $this->info($query->count() . ' bad subscriptions left to be removed');

        $this->info($report->count() . ' to be removed now');
    }
}
