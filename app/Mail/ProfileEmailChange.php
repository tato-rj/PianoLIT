<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ProfileEmailChange extends Mailable
{
    public $confirmationUrl;

    public function __construct($confirmationUrl)
    {
        $this->confirmationUrl = $confirmationUrl;
    }

    public function build()
    {
        return $this->subject('Confirm your new PianoLIT email')->markdown('emails.account.confirm-email-change');
    }
}
