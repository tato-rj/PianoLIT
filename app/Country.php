<?php

namespace App;

class Country extends PianoLit
{
    // Keep existing country/composer JSON contracts unchanged for mobile clients.
    protected $hidden = ['continent'];

    public function composers()
    {
    	return $this->hasMany(Composer::class);
    }

    public function pieces()
    {
    	return $this->hasManyThrough(Piece::class, Composer::class);
    }
}
