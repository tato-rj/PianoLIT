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

    public $subscriber, $lesson, $subject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(CrashCourseSubscription $subscription)
    {
        $this->subscriber = $subscription->subscriber;
        $this->lesson = $subscription->upcomingLesson;
        $this->subject = $this->makeSubject($subscription, $subscription->upcomingLesson);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subject)->markdown('emails.crashcourse');
    }

    public function makeSubject(CrashCourseSubscription $subscription, CrashCourseLesson $lesson)
    {
        if (! strhas($lesson->subject, '['))
            return $lesson->subject;
        
        preg_match_all("/\[([^\]]*)\]/", $lesson->subject, $matches);
        
        $placeholder = $matches[0][0];
        
        $key = $matches[1][0];

        return str_replace($placeholder, $subscription->$key, $lesson->subject);
    }
}
