<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\{Composer, Subscription};
use App\Mail\Timeline\OnThisDay;

class SendTimelineEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pianolit:timeline-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily emails with birthdays along with historical events that happened around that time.';

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
        $composer = Composer::bornToday()->inRandomOrder()->first();

        if ($composer->exists()) {
            foreach (Subscription::timeline()->get() as $subscriber) {
                \Mail::to($subscriber->email)->send(new OnThisDay($composer)); 
            }
        }
    }
}
