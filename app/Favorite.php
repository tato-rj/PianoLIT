<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Favorite extends PianoLit
{
    public function piece()
    {
    	return $this->belongsTo(Piece::class);
    }

    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    public function folders()
    {
    	return $this->belongsToMany(FavoriteFolder::class);
    }
}
