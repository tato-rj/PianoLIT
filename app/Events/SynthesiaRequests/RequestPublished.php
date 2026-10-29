<?php

namespace App\Events\SynthesiaRequests;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\SynthesiaRequest;

class RequestPublished
{
    use Dispatchable, SerializesModels;

    public $request;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(SynthesiaRequest $request)
    {
        $this->request = $request;
    }
}
