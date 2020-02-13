<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\EmailList;
use App\Mail\Traits\Trackable;

class NewsletterEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, Trackable;

    public $piece, $subscription, $list, $list_id, $subject;

    public function __construct($list_id, $subscription = null)
    {
        $this->subscription = $subscription;
        $this->list = EmailList::newsletter();
        $this->list_id = $list_id;
        $this->subject = request('subject') ?? 'PianoLIT Newsletter';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->track($this->list, $this->list_id, $this->subscription);

        return $this->subject($this->subject)->markdown('emails.lists.newsletter');
    }

    public function tags()
    {
        return ['email lists', 'newsletter'];
    }
}
