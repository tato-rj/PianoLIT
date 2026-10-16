<?php

namespace App\Notifications;

use App\FeedbackSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FeedbackSubmittedNotification extends Notification
{
    use Queueable;

    protected $feedback;

    public function __construct(FeedbackSubmission $feedback)
    {
        $this->feedback = $feedback;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $user = $this->feedback->user;
        $sender = $user ? (trim($user->full_name) ?: $user->email) : 'A guest';
        $rating = FeedbackSubmission::RATINGS[$this->feedback->rating];

        return [
            'title' => 'New feedback',
            'message' => '<strong>'.e($sender).'</strong> submitted feedback: <strong>'.e($rating).'</strong>.',
            'url' => route('admin.feedback.index'),
            'feedback_id' => $this->feedback->id,
        ];
    }
}
