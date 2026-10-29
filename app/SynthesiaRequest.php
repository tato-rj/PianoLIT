<?php

namespace App;

class SynthesiaRequest extends PianoLit
{
    // Explicit for serialized legacy TutorialRequest instances restored by workers.
    protected $table = 'synthesia_requests';
    protected $dates = ['published_at'];
    
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
