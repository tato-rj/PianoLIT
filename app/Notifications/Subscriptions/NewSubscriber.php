<?php

namespace App\Notifications\Subscriptions;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Subscription;

class NewSubscriber extends Notification
{
    use Queueable;

    protected $subscriber;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Subscription $subscriber)
    {
        $this->subscriber = $subscriber;
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
            'title' => 'New subscriber',
            'message' => '<strong>' . $this->subscriber->email . '</strong> subscribed to our newsletter.',
            'url' => route('admin.subscriptions.index')
        ];
    }
}
