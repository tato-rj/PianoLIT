<?php

namespace App\Mail;

use App\EmailList;
use App\Mail\Traits\Trackable;
use App\Services\Admin\EmailListTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailListEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, Trackable;

    public $list, $list_id, $subscription;

    public function __construct(EmailList $list, $list_id, $subscription = null)
    {
        $this->list = $list;
        $this->list_id = $list_id;
        $this->subscription = $subscription;
    }

    public function build()
    {
        $this->track($this->list, $this->list_id, $this->subscription);

        return $this->subject($this->subject ?: $this->list->name)
            ->markdown('emails.lists.'.EmailListTemplates::slug($this->list->name));
    }

    public function tags()
    {
        return ['email lists', EmailListTemplates::slug($this->list->name)];
    }
}
