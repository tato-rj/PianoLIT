<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\StudioPolicy;

class NewStudioPolicyNotification extends Notification
{
    use Queueable;

    protected $policy;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(StudioPolicy $policy)
    {
        $this->policy = $policy;
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
            'title' => 'Studio Policy created',
            'message' => '<strong>' . $this->policy->user->first_name . '</strong> created a new studio policy.',
            'url' => route('admin.users.show', $this->policy->user->id)
        ];
    }
}
