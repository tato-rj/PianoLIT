<?php

namespace App\Events\Performances;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Performance;

class PerformanceApproved
{
    use Dispatchable, SerializesModels;

    public $performance;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Performance $performance)
    {
        $this->performance = $performance;
    }
}
