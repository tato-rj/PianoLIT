<?php

namespace App;

class Composer extends PianoLit
{
    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }
}
