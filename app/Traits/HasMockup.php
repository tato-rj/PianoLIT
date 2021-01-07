<?php

namespace App\Traits;

trait HasMockup
{
    public function mockup_image()
    {
        return $this->mockup_path ? 
        		asset('storage/' . $this->mockup_path) : 
        		null;
    }
}