<?php

namespace App;

class FavoriteFolder extends PianoLit
{
    protected $withCount = ['favorites'];
    protected $casts = ['is_default' => 'boolean'];

    public static function boot()
    {
        parent::boot();

        self::deleting(function($folder){
            $folder->favorites->each->delete();
        });
    }

	public function user()
	{
		return $this->belongsTo(User::class);
	}

    public function favorites()
    {
        return $this->hasMany(Favorite::class)->with('piece')->latest();
    }

    public function scopeUserCreated($query)
    {
        $teamIds = User::team()->pluck('id');

        return $this->where('is_default', false)->whereNotIn('user_id', $teamIds);
    }
    
    public function scopeRetrieve($query, $userId, $folderId)
    {
    	return $query->where(['id' => $folderId, 'user_id' => $userId])->exists();
    }

    public function scopeFlat($query, $userId, $folderId)
    {
        $collection = $query->where(['id' => $folderId, 'user_id' => $userId])->with('favorites')->first();

        return $collection->favorites->pluck('piece')->each->isFavorited($userId);
    }

    public function getComposersAttribute()
    {
        $composers = collect();

        $this->favorites->each(function($favorite) use ($composers) {
            $composers->push($favorite->piece->composer);
        });

        return $composers->unique();
    }

    public function hasPiece($piece_id)
    {
        $this->has_piece = $this->favorites()->where('piece_id', $piece_id)->exists();
    }

    public function scopeDatatable($query)
    {
        return datatable($query)->withDate()->withCount(['favorites'])->withBlade([
            'user' => view('admin.pages.stats.favorites.table.user'),
            'action' => view('admin.pages.stats.favorites.table.actions'),
        ])->make();
    }
}
