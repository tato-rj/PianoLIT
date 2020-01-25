<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\{Piece, EmailList};
use App\Mail\Traits\Trackable;

class FreePickEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, Trackable;

    public $piece, $subscription, $list;

    public function __construct($subscription = null)
    {
        $this->piece = Piece::free()->first();
        $this->subscription = $subscription;
        $this->list = EmailList::freepick();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->track($this->list, $this->subscription);

        return $this->subject('Free weekly pick')->markdown('emails.lists.freepick');
    }

    public function tags()
    {
        return ['email lists', 'free pick'];
    }
}
