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

        // if ($composersBorn->count() + $composersDied->count() > 0) {          
        //     foreach ($composersBorn as $composer) {
        //         $this->info($composer->name . ' was born ' . now()->diffInYears($composer->date_of_birth) . ' years ago on ' . $composer->date_of_birth->toFormattedDateString() . '.');
        //         $timeline = Timeline::fromYear($composer->date_of_birth->year);
        //         if ($timeline->exists()) {
        //             $this->info('In that same year...');
        //             foreach ($timeline->get() as $event) {
        //                 $this->info($event->event);
        //             }
        //         }
        //     }

        //     foreach ($composersDied as $composer) {
        //         $this->info($composer->name . ' died ' . now()->diffInYears($composer->date_of_death) . ' years ago on ' . $composer->date_of_death->toFormattedDateString() . '.');
        //         $timeline = Timeline::fromYear($composer->date_of_death->year);
        //         if ($timeline->exists()) {
        //             $this->info('In that same year...');
        //             foreach ($timeline->get() as $event) {
        //                 $this->info($event->event);
        //             }
        //         }
        //     }
        // }    

        if ($composersBorn->count() + $composersDied->count() > 0)
            \Mail::to('arthurvillar@gmail.com')->send(new OnThisDay($composersBorn, $composersDied));
    }
}
