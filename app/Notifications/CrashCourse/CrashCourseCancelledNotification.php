<?php

namespace App\Notifications\CrashCourse;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\CrashCourse\CrashCourseSubscription;

class CrashCourseCancelledNotification extends Notification
{
    use Queueable;

    protected $subscription;

    public function __construct(CrashCourseSubscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => 'Crash Course cancelled',
            'message' => $this->subscription->first_name . ' cancelled the <strong>' . $this->subscription->crashcourse->title . '</strong> crash course.',
            'url' => route('admin.crashcourses.index', $this->subscription->crashcourse)
        ];
    }
}
