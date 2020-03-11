<?php

namespace App\Listeners\CrashCourses;

use App\Events\CrashCourses\CrashCourseSignUp;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CrashCourseSignUpListener
{
    /**
     * Handle the event.
     *
     * @param  CrashCourseSignUp  $event
     * @return void
     */
    public function handle(CrashCourseSignUp $event)
    {
        $event->subscription->start();
    }
}
