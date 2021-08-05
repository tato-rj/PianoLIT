<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\{Piece, User};

class SharePieceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $piece, $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Piece $piece, User $user)
    {
        $this->piece = $piece;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $name = $this->user->first_name;

        return $this->subject($name . ' shared a piece with you!')->markdown('emails.share-piece');
    }
}
