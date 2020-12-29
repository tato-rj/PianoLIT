<?php

namespace App\Notifications\Memberships;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AppleMembershipsValidated extends Notification
{
    use Queueable;

    protected $count;

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
            'title' => 'Memberships validated',
            'message' => 'All Apple memberships were re-validated (' . $this->count . ' reactivated).',
            'url' => route('admin.users.index')
        ];
    }
}
