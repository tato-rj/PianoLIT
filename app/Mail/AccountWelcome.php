<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class AccountWelcome extends Mailable implements ShouldQueue
{
    use Queueable;

    public $firstName;

    public function __construct(string $firstName)
    {
        $this->firstName = $firstName;
    }

    public function build()
    {
        return $this->subject('Welcome to PianoLIT!')->markdown('emails.account-welcome');
    }
}
