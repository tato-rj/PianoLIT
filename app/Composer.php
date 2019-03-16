<?php

namespace App;

class Composer extends PianoLit
{
    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function pieces()
    {
    	return $this->hasMany(Piece::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
