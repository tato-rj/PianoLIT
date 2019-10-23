<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Gift extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $gift, $subscriber;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($gift, $subscriber)
    {
        $this->gift = $gift;
        $this->subscriber = $subscriber;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('A gift from PianoLIT')->markdown('emails.gift');
    }
}
