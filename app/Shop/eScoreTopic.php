<?php

namespace App\Shop;

use App\Traits\FindBySlug;
use App\{PianoLit, Admin};

class eScoreTopic extends PianoLit
{
	use FindBySlug;

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($topic) {
            $topic->eScores()->detach();
        });
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function eScores()
    {
        return $this->belongsToMany(eScore::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }
}
