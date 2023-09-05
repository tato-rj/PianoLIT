<?php

namespace App\Mail\Performances;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Piece;

class PerformanceSubmittedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $piece;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Piece $piece)
    {
        $this->piece = $piece;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Performance submitted')->markdown('emails.performances.submitted')->with(['piece' => $this->piece]);
    }
}
