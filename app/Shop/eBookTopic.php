<?php

namespace App\Shop;

use App\Traits\FindBySlug;
use App\{PianoLit, Admin};

class eBookTopic extends PianoLit
{
	use FindBySlug;

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($topic) {
            $topic->eBooks()->detach();
        });
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function eBooks()
    {
        return $this->belongsToMany(eBook::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }
}
