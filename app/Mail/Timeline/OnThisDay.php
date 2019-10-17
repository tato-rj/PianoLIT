<?php

namespace App\Mail\Timeline;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Timeline;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OnThisDay extends Mailable
{
    use Queueable, SerializesModels;

    public $composersBorn, $composersDied;

    public function __construct($composersBorn, $composersDied)
    {
        $this->composersBorn = $composersBorn;
        $this->composersDied = $composersDied;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('What happened on this day...')->markdown('emails.timeline.daily');
    }
}
