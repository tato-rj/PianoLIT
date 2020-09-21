<?php

namespace App;

class FavoriteFolder extends PianoLit
{
    public function pieces()
    {
        return $this->hasManyThrough(Piece::class, Favorite::class);
    }
}
