<?php

namespace App\Notifications\Subscriptions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FailedSubscriptionsRemoved extends Notification
{
    use Queueable;

    protected $count;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($count)
    {
        $this->count = $count;
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
            'title' => 'Subscription list cleaned up',
            'message' => '<strong>' . $this->count . '</strong> failed emails have been removed.',
            'url' => ''
        ];
    }
}
