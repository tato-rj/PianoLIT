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

    public $subscription, $list;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subscription = null)
    {
        $this->list = EmailList::test();
        $this->subscription = $subscription;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->track($this->list, $this->subscription);

        return $this->subject('Test email')->markdown('emails.lists.test');
    }

    public function tags()
    {
        return ['email lists', 'test email'];
    }
}
