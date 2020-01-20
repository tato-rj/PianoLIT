<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Piece;

class FreePickEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $piece;

    public function __construct()
    {
        $this->piece = Piece::free()->first();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Free weekly pick')->markdown('emails.lists.freepick');
    }
}
