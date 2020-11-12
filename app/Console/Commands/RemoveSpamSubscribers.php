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
        $count = 0;
        $report = EmailLog::whereNotNull('failed_at')->take(200)->get();

        $report->each(function($spam) use ($count) {
            $user = User::byEmail($spam->recipient);
            $subscription = Subscription::byEmail($spam->recipient);

            if (! $user->exists() && $subscription->exists()) {
                // $subscription->first()->delete();
                $this->info('Removing ' . $subscription->first()->email);
                $count += 1;
            }
        });

        $this->info($count . ' bad subscriptions have been removed');        

        $this->info(EmailLog::whereNotNull('failed_at')->count() . ' to go');
    }
}
