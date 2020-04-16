<?php

namespace App;

use App\Traits\Sortable;

class Playlist extends PianoLit
{
    use Sortable;
    
    protected $appends = ['cover_image'];
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
