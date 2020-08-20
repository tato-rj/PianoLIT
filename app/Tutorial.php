<?php

namespace App;

class Tutorial extends PianoLit
{
	protected $types = ['Performance', 'Tutorial', 'Harmonic analysis'];
	
	public function piece()
	{
		return $this->belongsTo(Piece::class);
	}

    public function getUrlAttribute()
    {
    	return config('services.googlecloud.videos') . str_slug($this->piece->composer->name) . '/' . $this->filename . '.mp4';
    }

    public function scopeTypes($query)
    {
    	return $this->types;
    }
}
