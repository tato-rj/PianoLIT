<?php

namespace App\Events\CrashCourses;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\CrashCourse\CrashCourseSubscription;

class CrashCourseFinished
{
    use Dispatchable, SerializesModels;

    public $subscription;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(CrashCourseSubscription $subscription)
    {
        $this->subscription = $subscription;
    }
}
