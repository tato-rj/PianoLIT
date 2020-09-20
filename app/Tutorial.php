<?php

namespace App;

class Tutorial extends PianoLit
{
	protected $types = ['Performance', 'Tutorial', 'Harmonic analysis'];
    protected $appends = ['title'];
	
    public static function boot()
    {
        parent::boot();

        self::created(function($tutorial) {
            $tutorial->generateUrl();
        });

        self::updating(function($tutorial) {
            $tutorial->generateUrl();
        });
    }

	public function piece()
	{
		return $this->belongsTo(Piece::class);
	}

    public function generateUrl()
    {
        if (! $this->piece()->exists())
            return null;
        
        $url = config('services.googlecloud.videos') . str_slug($this->piece->composer->name) . '/' . $this->filename . '.mp4';

    	return $this->video_url != $url ? $this->update(['video_url' => $url]) : $this;
    }

    public function getTitleAttribute()
    {
        return $this->type;
    }

    public function scopeTypes($query)
    {
    	return $this->types;
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', 'LIKE', '%'.ucfirst($type).'%');
    }
}
