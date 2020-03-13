<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\CrashCourse\{CrashCourseSubscription, CrashCourseLesson};

class CrashCourseEmailPreview extends Mailable
{
    use Queueable, SerializesModels;

    public $lesson, $subscription;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(CrashCourseLesson $lesson)
    {
        $this->subscription = null;
        $this->lesson = $lesson;
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
}
