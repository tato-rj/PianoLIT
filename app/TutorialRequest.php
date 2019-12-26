<?php

namespace App;

class TutorialRequest extends PianoLit
{
    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    public function piece()
    {
    	return $this->belongsTo(Piece::class);
    }

    public function isPublished()
    {
    	return ! is_null($this->published_at);
    }
}
