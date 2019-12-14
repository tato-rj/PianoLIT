<?php

namespace App;

class StudioPolicy extends PianoLit
{
    public function user()
    {
    	return $this->belongsTo(User::class);
    }
}
