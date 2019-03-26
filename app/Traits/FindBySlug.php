<?php

namespace App\Traits;

trait FindBySlug
{
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function scopeBySlug($query, $slug)
    {
    	return $query->where('slug', $slug)->first();
    }
}
