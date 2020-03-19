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

    public $subscription, $lesson, $email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($model, $email = null)
    {
        $this->manageData($model);
        $this->email = $email;
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
        } else if (get_class($model) == CrashCourseLesson::class) {
            $this->subscription = null;
            $this->lesson = $model;
        } else {
            throw new \Exception('You must pass either a lesson or a subscription to this email', 404);
        }
    }
}
