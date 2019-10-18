<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\{Composer, Timeline};
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
    protected $description = 'Send daily emails with historical events happening on that day.';

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
        $composersBorn = Composer::bornToday()->get();
        $composersDied = Composer::diedToday()->get();

        // if ($composersBorn->count() + $composersDied->count() > 0)
            \Mail::to('arthurvillar@gmail.com')->send(new OnThisDay($composersBorn, $composersDied));
    }
}
