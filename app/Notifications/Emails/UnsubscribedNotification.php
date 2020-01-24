<?php

namespace App\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\{EmailList, Subscription};

class UnsubscribedNotification extends Notification
{
    use Queueable;

    protected $list, $subscription;

    public function __construct(EmailList $list, Subscription $subscription)
    {
        $this->list = $list;
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
            'title' => 'Email unsubscribed',
            'message' => $this->subscription->email . ' has <span class="text-danger">unsubscribed</span> from the <strong>' . $this->list->name . '</strong> list.',
            'url' => route('admin.subscriptions.lists.index')
        ];
    }
}
