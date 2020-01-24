<?php

namespace App\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\EmailList;

class EmailListSentNotification extends Notification
{
    use Queueable;

    protected $list;

    public function __construct(EmailList $list)
    {
        $this->list = $list;
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
            'title' => 'Email list sent',
            'message' => '<strong>' . $this->list->name . '</strong> email was sent to all subscribers.',
            'url' => route('admin.subscriptions.lists.index')
        ];
    }
}
