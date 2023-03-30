<?php

namespace App;

class Rating extends PianoLit
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
