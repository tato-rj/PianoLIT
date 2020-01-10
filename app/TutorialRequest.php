<?php

namespace App;

class TutorialRequest extends PianoLit
{
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

    public function scopeDatatable($query)
    {
        return datatable($query->with(['user', 'piece.composer']))->withDate(['created_at', 'published_at'])->withBlade([
            'action' => view('admin.pages.requests.actions')
        ])->make();
    }
}
