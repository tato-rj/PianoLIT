<?php

namespace App;

class Country extends PianoLit
{
    public function composers()
    {
    	return $this->hasMany(Composer::class);
    }

    public function pieces()
    {
    	return $this->hasManyThrough(Piece::class, Composer::class);
    }
}
