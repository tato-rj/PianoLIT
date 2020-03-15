<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\CrashCourse\CrashCourseSubscription;

class CrashCourseFeedbackEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $subscription, $first_name, $crashcourse;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($crashcourse, $model = null)
    {
        $this->crashcourse = $crashcourse;
        $this->manageData($model);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Crash course completed 🤗')->markdown('emails.crashcourse-feedback');
    }

    public function manageData($model)
    { 
        if (get_class($model) == CrashCourseSubscription::class) {
            $this->subscription = $model;
        } else {
            $this->subscription = null;
        }
    }
}
