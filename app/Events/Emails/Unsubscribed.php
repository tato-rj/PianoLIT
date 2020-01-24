<?php

namespace App\Events\Emails;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\{EmailList, Subscription};

class Unsubscribed
{
    use Dispatchable, SerializesModels;
    
    public $list, $subscription;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(EmailList $list, Subscription $subscription)
    {
        $this->list = $list;
        $this->subscription = $subscription;
    }
}
