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
    public function __construct($crashcourse, $model)
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
        if (is_string($model)) {
            $this->subscription = null;
            $this->first_name = $model; 
        } else if (get_class($model) == CrashCourseSubscription::class) {
            $this->subscription = $model;
            $this->first_name = $this->subscription->first_name; 
        } else {
            throw new \Exception('You must pass either a name or a subscription to this email', 404);
        }
    }
}
