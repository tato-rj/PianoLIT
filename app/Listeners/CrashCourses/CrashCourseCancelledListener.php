<?php

namespace App\Listeners\CrashCourses;

use App\Events\CrashCourses\CrashCourseSignUp;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\CrashCourse\CrashCourseCancelledNotification;
use App\Events\CrashCourses\CrashCourseCancelled;
use App\Admin;

class CrashCourseCancelledListener
{
    /**
     * Handle the event.
     *
     * @param  CrashCourseCancelled  $event
     * @return void
     */
    public function handle(CrashCourseCancelled $event)
    {
        Admin::notifyAll(new CrashCourseCancelledNotification($event->subscription));
    }
}
