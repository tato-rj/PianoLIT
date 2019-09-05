<?php

namespace App;

class Playlist extends PianoLit
{
    protected $withCount = ['pieces'];
    protected $with = ['pieces'];

    public static function boot()
    {
        parent::boot();

        self::deleting(function($playlist) {
            $playlist->pieces()->detach();
        });
    }

    public function pieces()
    {
    	return $this->belongsToMany(Piece::class)->orderBy('order');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function scopeJourney($query)
    {
        return $query->where('group', 'journey');        
    }
    
	public function scopeFoundation($query)
	{
		return $query->whereIn('name', ['Basic 1', 'Basic 2', 'Basic 3']);
	}
}
