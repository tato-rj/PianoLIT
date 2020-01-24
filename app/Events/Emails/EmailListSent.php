<?php

namespace App\Events\Emails;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\EmailList;

class EmailListSent
{
    use Dispatchable, SerializesModels;

    public $list;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(EmailList $list)
    {
        $this->list = $list;
    }
}
