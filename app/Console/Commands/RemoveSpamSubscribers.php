<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\EmailLog;

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

        $spam = $query->take(500)->get();

        $this->info($query->count() . ' bad subscriptions left to be removed');
        
        $this->info($spam->count() . ' to be removed now');
    }
}
