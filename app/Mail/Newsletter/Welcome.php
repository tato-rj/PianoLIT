<?php

namespace App\Mail\Newsletter;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\{Subscription, EmailList};

class Welcome extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subscription, $list;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
        $this->list = EmailList::newsletter();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Welcome to our Newsletter')->markdown('emails.newsletter.welcome');
    }
}
