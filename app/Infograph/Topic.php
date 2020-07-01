<?php

namespace App\Infograph;

use App\Traits\FindBySlug;
use App\{PianoLit, Admin};

class Topic extends PianoLit
{
	use FindBySlug;

    protected $table = 'infograph_topics';

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($topic) {
            $topic->infographs()->detach();
        });
    }
    
    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function infographs()
    {
        return $this->belongsToMany(Infograph::class, 'infograph_infograph_topic');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }
}
