<?php

namespace App\Listeners\CrashCourses;

use App\Events\CrashCourses\CrashCourseSignUp;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\CrashCourse\CrashCourseFinishedNotification;
use App\Events\CrashCourses\CrashCourseFinished;
use App\Admin;

class CrashCourseFinishedListener
{
    /**
     * Handle the event.
     *
     * @param  CrashCourseFinished  $event
     * @return void
     */
    public function handle(CrashCourseFinished $event)
    {
        Admin::notifyAll(new CrashCourseFinishedNotification($event->subscription));
    }
}
