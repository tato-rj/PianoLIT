<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Piece;

class PieceSharedNotification extends Notification
{
    use Queueable;

    protected $piece;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Piece $piece)
    {
        $this->piece = $piece;
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
            'title' => 'Piece shared',
            'message' => auth()->user()->full_name . ' just shared ' . $this->piece->medium_name_with_composer,
            'url' => route('admin.users.show', auth()->user())
        ];
    }
}
