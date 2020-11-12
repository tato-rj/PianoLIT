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

    protected $count = 0;

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
        EmailLog::whereNotNull('failed_at')->chunk(400, function($report) {
            $this->incrementCount();
            foreach($report as $spam) {
                $user = User::byEmail($spam->recipient);
                $subscription = Subscription::byEmail($spam->recipient);

                if (! $user->exists() && $subscription->exists()) {
                    $this->info('Removing ' . $subscription->first()->email);
                    $subscription->first()->delete();
                }
            }
        });

        $this->info('All done ' . $this->count);
    }

    public function incrementCount()
    {
        $this->count += 1;
    }
}
