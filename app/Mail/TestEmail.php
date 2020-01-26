<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\EmailList;
use App\Mail\Traits\Trackable;

class TestEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, Trackable;

    public $subscription, $list, $list_id;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($list_id, $subscription = null)
    {
        $this->list = EmailList::test();
        $this->subscription = $subscription;
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

        return $this->subject('Test email')->markdown('emails.lists.test');
    }

    public function tags()
    {
        return ['email lists', 'test email'];
    }
}
