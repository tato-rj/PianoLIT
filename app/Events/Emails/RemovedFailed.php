<?php

namespace App\Events\Emails;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemovedFailed
{
    use Dispatchable, SerializesModels;

    public $count;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($count)
    {
        $this->count = $count;
    }
}
