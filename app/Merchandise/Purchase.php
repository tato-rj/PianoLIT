<?php

namespace App\Merchandise;

use App\PianoLit;

class Purchase extends PianoLit
{
    public function item()
    {
        return $this->morphTo();
    }

    public function scopeFree($query)
    {
    	return $query->whereNull('cost');
    }

    public function scopePaid($query)
    {
    	return $query->whereNotNull('cost');
    }
}
