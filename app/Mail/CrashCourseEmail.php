<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\CrashCourse\{CrashCourseSubscription, CrashCourseLesson};

class CrashCourseEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $subscription, $lesson, $cancel_url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($model)
    {
        $this->manageData($model);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->lesson->dynamic('subject', $this->subscription))
                    ->markdown('emails.crashcourse');
    }

    public function manageData($model)
    {
        if (get_class($model) == CrashCourseSubscription::class) {
            $this->subscription = $model;
            $this->lesson = $this->subscription->upcomingLesson;
            $this->cancel_url = route('crashcourses.cancel', $this->subscription);
        } else if (get_class($model) == CrashCourseLesson::class) {
            $this->subscription = null;
            $this->lesson = $model;
            $this->cancel_url = null;
        } else {
            throw new \Exception('You must pass either a lesson or a subscription to this email', 404);
        }
    }
}
