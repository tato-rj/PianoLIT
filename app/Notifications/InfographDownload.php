<?php

namespace App\Notifications;

use App\Infograph;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class InfographDownload extends Notification
{
    use Queueable;

    protected $infograph;

    public function __construct(Infograph $infograph)
    {
        $this->infograph = $infograph;
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
            'title' => 'Infograph download',
            'message' => 'New download for the <strong>' . $this->infograph->name . '</strong> infograph.',
            'url' => route('admin.stats.infographs')
        ];
    }
}
