<?php

namespace App\Mail\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\Traits\Trackable;

class AdminReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, Trackable;

    public $reports, $recipient, $list_id, $list;

    public function __construct($reports, $recipient, $list_id)
    {
        $this->reports = $reports;
        $this->recipient = $recipient;
        $this->list = Admin::managers()->get();
        $this->list_id = $list_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->track($this->list, $this->list_id, $this->recipient);

        return $this->subject('PianoLIT weekly report')->markdown('emails.admin.report.index');
    }
}
