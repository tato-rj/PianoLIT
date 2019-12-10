<?php

namespace App\Merchandise;

use App\PianoLit;

class Purchase extends PianoLit
{
    public function item()
    {
        return $this->morphTo();
    }
}
