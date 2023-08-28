<?php

namespace App\Notifications\Performances;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Performance;

class PerformanceSubmittedNotification extends Notification
{
    use Queueable;

    protected $performance;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Performance $performance)
    {
        $this->performance = $performance;
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
            'title' => 'New performance submitted',
            'message' => '<strong>' . $this->performance->user->first_name . '</strong> submitted a new performance.',
            'url' => route('admin.stage.index')
        ];
    }
}
