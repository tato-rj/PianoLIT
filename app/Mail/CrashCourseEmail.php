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

    public $subscription, $lesson;
    public $action;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($model, $email = null, $lesson = null)
    {
        $this->manageData($model, $lesson);
        $this->action = $email ? route('crashcourses.cancel', ['email' => $email]) : null;
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

    public function manageData($model, $lesson = null)
    {
        if (get_class($model) == CrashCourseSubscription::class) {
            $this->subscription = $model;
            $this->lesson = $lesson ?? $this->subscription->upcomingLesson;
        } else if (get_class($model) == CrashCourseLesson::class) {
            $this->subscription = null;
            $this->lesson = $lesson ?? $model;
        } else {
            throw new \Exception('You must pass either a lesson or a subscription to this email', 404);
        }
    }
}
