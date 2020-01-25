<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\Traits\Trackable;
use App\EmailList;

class LatestTutorialsEmail extends Mailable
{
    use Queueable, SerializesModels, Trackable;

    public $subscription, $list;

    public function __construct($subscription = null)
    {
        $this->subscription = $subscription;
        $this->list = EmailList::tutorials();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->track($this->list, $this->subscription);
        
        return $this->subject('Latest tutorials')->markdown('emails.lists.tutorials');
    }

    public function tags()
    {
        return ['email lists', 'latest tutorials'];
    }
}
