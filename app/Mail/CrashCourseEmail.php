<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\CrashCourse\{CrashCourseSubscription, CrashCourseLesson};

class CrashCourseEmail extends Mailable
{
    use Queueable, SerializesModels, \App\Mail\Traits\EditorialLayout;

    public $emailDesign;
    public $builderPreview = false;
    public $theme = 'default';

    public $subscription, $lesson;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($model, $next = true)
    {
        $this->manageData($model, $next);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $designs = app(\App\Services\CrashCourses\EmailDesign::class);
        $saved = $this->emailDesign === null ? $designs->saved($this->lesson) : null;
        $design = $this->emailDesign ?? ($saved ? json_decode($saved->design, true) : null);
        if ($design) {
            $this->theme = 'welcome';
            return $this->subject($this->lesson->dynamic('subject', $this->subscription))
                ->markdown('emails.courses.lesson', ['design' => $designs->personalize($design, $this->subscription)]);
        }
        return $this->subject($this->lesson->dynamic('subject', $this->subscription))
                    ->markdown('emails.crashcourse');
    }

    public function manageData($model, $next)
    {
        if (get_class($model) == CrashCourseSubscription::class) {
            $this->subscription = $model;
            $this->lesson = $next ? $this->subscription->upcomingLesson : $this->subscription->previousLesson;
        } else if (get_class($model) == CrashCourseLesson::class) {
            $this->subscription = null;
            $this->lesson = $model;
        } else {
            throw new \Exception('You must pass either a lesson or a subscription to this email', 404);
        }
    }
}
