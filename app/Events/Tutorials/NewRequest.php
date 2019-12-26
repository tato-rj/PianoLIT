<?php

namespace App\Events\Tutorials;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\TutorialRequest;

class NewRequest
{
    use Dispatchable, SerializesModels;

    public $request;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TutorialRequest $request)
    {
        $this->request = $request;
    }
}
