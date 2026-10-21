<?php

namespace App;

class PlaylistTag extends PianoLit
{
    protected $hidden = ['name_key'];

    public function playlists()
    {
        return $this->hasMany(Playlist::class, 'featured_tag_id');
    }
}
