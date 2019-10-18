<?php

namespace App\Mail\Timeline;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Timeline;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OnThisDay extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $composer, $history;

    public function __construct($composer)
    {
        if (! $composer)
            abort(413, 'No composer was born today.');

        $this->composer = $composer;
        $this->history = Timeline::aroundYear($composer->born_in, 10)->get();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->composer->last_name . '\'s birthday today!')->markdown('emails.timeline.daily');
    }
}
