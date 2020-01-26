<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\Traits\Trackable;
use App\EmailList;

class LatestTutorialsEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, Trackable;

    public $subscription, $list, $list_id;

    public function __construct($list_id, $subscription = null)
    {
        $this->subscription = $subscription;
        $this->list = EmailList::tutorials();
        $this->list_id = $list_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->track($this->list, $this->list_id, $this->subscription);
        
        return $this->subject('Our latest tutorials')->markdown('emails.lists.tutorials');
    }

    public function tags()
    {
        return ['email lists', 'latest tutorials'];
    }
}
