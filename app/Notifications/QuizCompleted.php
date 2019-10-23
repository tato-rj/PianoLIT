<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Quiz\Quiz;

class QuizCompleted extends Notification
{
    use Queueable;

    protected $quiz;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Quiz $quiz)
    {
        $this->quiz = $quiz;
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
            'title' => 'Quiz completed',
            'message' => 'The quiz <strong>' . $this->quiz->title . '</strong> has been completed.',
            'url' => route('admin.stats.quizzes')
        ];
    }
}
