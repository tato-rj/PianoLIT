<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\StudioPolicy;

class StudioPolicyCreated
{
    use Dispatchable, SerializesModels;

    public $policy;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(StudioPolicy $policy)
    {
        $this->policy = $policy;
    }
}
