<?php

namespace App\Notifications\SynthesiaRequests;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\SynthesiaRequest;

class NewRequestNotification extends Notification
{
    use Queueable;

    protected $request;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(SynthesiaRequest $request)
    {
        $this->request = $request;
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
            'title' => 'New Synthesia request',
            'message' => '<strong>' . $this->request->user->first_name . '</strong> requested a Synthesia video.',
            'url' => route('admin.synthesia-requests.index')
        ];
    }
}
