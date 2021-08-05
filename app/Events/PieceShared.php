<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Piece;

class PieceShared
{
    use Dispatchable, SerializesModels;

    public $piece, $recipient;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Piece $piece, $recipient)
    {
        $this->recipient = $recipient;
        $this->piece = $piece;
    }
}
