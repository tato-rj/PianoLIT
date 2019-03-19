<?php

namespace App;

class Playlist extends PianoLit
{
	public function scopeFoundation($query)
	{
		return $query->whereIn('name', ['Basic 1', 'Basic 2', 'Basic 3']);
	}

    public function pieces()
    {
    	return $this->belongsToMany(Piece::class);
    }
}
