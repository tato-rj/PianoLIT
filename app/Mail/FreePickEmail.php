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

    public $piece, $subscription, $list, $list_id, $tutorials;

    public function __construct($list_id, $subscription = null)
    {
        $this->piece = Piece::find(940);//Piece::free()->first();
        $this->tutorials = $this->piece->tutorials()->get()->toArray();
        $this->subscription = $subscription;
        $this->list = EmailList::freepick();
        $this->list_id = $list_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        dd('now here');
        $this->track($this->list, $this->list_id, $this->subscription, $this->tutorials);

        return $this->subject('Your free pick this week')->markdown('emails.lists.freepick');
    }

    public function tags()
    {
        return ['email lists', 'free pick'];
    }
}
