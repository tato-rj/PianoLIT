<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\CrashCourse\CrashCourseSubscription;

class SendCrashCourseEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crashcourse:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically send all upcoming crash course lessons';

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
        $subscriptions = CrashCourseSubscription::active()->get();

        foreach ($subscriptions as $subscription) {
            $subscription->continue();
        }
    }
}
