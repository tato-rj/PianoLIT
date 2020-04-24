<?php

namespace App;

use App\Traits\Sortable;

class Playlist extends PianoLit
{
    use Sortable;
    
    protected $appends = ['cover_image', 'is_featured'];
    protected $withCount = ['pieces'];

    public static function boot()
    {
        parent::boot();

        self::deleting(function($playlist) {
            $playlist->pieces()->detach();
            \Storage::disk('public')->delete($playlist->cover_path);
        });
    }

    public function pieces()
    {
    	return $this->belongsToMany(Piece::class)->with(['composer', 'tags'])->orderBy('order');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function scopeComplete($query)
    {
        $playlists = $query->with('pieces')->get();

        $playlists->each(function($playlist) {
            $playlist->pieces->each(function($piece, $index) use ($playlist) {
                if (! $piece->hasVideos()) {
                    $playlist->pieces->forget($index);
                    $playlist->pieces_count = $playlist->pieces_count - 1;
                }
            });
        });

        return $playlists;
    }

    public function scopeFeatured($query)
    {
        return $query->where('order', 0);
    }

    public function getIsFeaturedAttribute()
    {
        return is_null($this->group) && $this->order == 0;
    }

    public function getCoverImageAttribute()
    {
        return storage($this->cover_path);
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);        
    }
    
	public function scopeFoundation($query)
	{
		return $query->whereIn('name', ['Basic 1', 'Basic 2', 'Basic 3']);
	}
}
