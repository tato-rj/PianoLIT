<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('pianolit:admin-report')->dailyAt('10:30');
        // $schedule->command('pianolit:admin-report')->weeklyOn(7, '22:30');

        $schedule->command('subscriptions:remove-spam')->weeklyOn(7, '10:30');

        // $schedule->command('pianolit:unconfirmed-emails')->weeklyOn(2, '10:00');
        
        $schedule->command('crashcourse:send')->dailyAt('6:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
