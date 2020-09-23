<?php

namespace App;

class FavoriteFolder extends PianoLit
{
    protected $withCount = ['favorites'];

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
        return $this->hasMany(Favorite::class);
    }

    public function scopeRetrieve($query, $userId, $folderId)
    {
    	return $query->where(['id' => $folderId, 'user_id' => $userId])->exists();
    }
}
