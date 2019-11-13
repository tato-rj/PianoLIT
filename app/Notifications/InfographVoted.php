<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Infograph\Infograph;

class InfographVoted extends Notification
{
    use Queueable;

    protected $infograph, $vote;

    public function __construct(Infograph $infograph, bool $vote)
    {
        $this->infograph = $infograph;
        $this->vote = $vote ? '<span class="text-green">Thumbs up</span>' : '<span class="text-danger">Thumbs down</span>';
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
            'title' => 'Infograph vote',
            'message' => $this->vote . ' for <strong>' . $this->infograph->name . '</strong> infograph.',
            'url' => route('admin.stats.infographs')
        ];
    }
}
