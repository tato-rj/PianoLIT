<?php

namespace App\Mail\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AdminReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $reports, $recipient;

    public function __construct($reports, $recipient)
    {
        $this->reports = $reports;
        $this->recipient = $recipient;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('PianoLIT weekly report')->markdown('emails.admin.report');
    }
}
