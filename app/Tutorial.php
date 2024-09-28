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

    public function getVideoUrlAttribute($attr)
    {
        return str_replace(
            ['https://storage.googleapis.com/pianolit-app/videos', 'https://storage.googleapis.com/pianolit-test/videos'], 
            'https://leftlaneapps.com/storage', 
            $attr
        );
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

    public function scopeSynthesia($query, $count)
    {
        return $query->byType('synthesia')
                           ->with('piece')
                           ->inRandomOrder()
                           ->take($count)
                           ->get()
                           ->each(function($item, $index) {
                                $item->thumbnail = asset('images/webapp/synthesia-thumbnails/thumb-'.$index.'.jpg');
                           })->shuffle();
    }

    public function scopeLatestHarmonicAnalysis($query, $count)
    {
        return $query->byType('harmonic')->latest()->with('piece')->take(12)->get()->unique('piece_id')->take($count);
    }

    public function scopeHarmonicAnalysis($query, $count)
    {
        return $query->byType('harmonic')->inRandomOrder()->with('piece')->take(12)->get()->unique('piece_id')->take($count);
    }
}
