<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\{User, FavoriteFolder};

class eScoreGeneratedNotification extends Notification
{
    use Queueable;

    protected $user, $folder;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, FavoriteFolder $folder)
    {
        $this->user = $user;
        $this->folder = $folder;
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
            'title' => 'eScore generated',
            'message' => '<strong>' . $this->user->first_name . '</strong> generated an eScore from folder ' . $this->folder->name . '.',
            'url' => route('admin.users.show', auth()->user())
        ];
    }
}
