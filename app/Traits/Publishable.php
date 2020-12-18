<?php

namespace App\Traits;

trait Publishable
{
    public function isPublished()
    {
        return ! is_null($this->published_at);
    }

    public function getIsNewAttribute()
    {
        return $this->published_at->gte(now()->subWeek());
    }

    public function updateStatus($attribute = null)
    {
        $attribute = $attribute ?? 'published_at';

        if ($this->$attribute) {
        	$this->update([$attribute => null]);
        } else {
            $this->update([$attribute => now()]);
        }
    }

    public function getStatusAttribute()
    {
        return $this->published_at ? 'published' : 'unpublished';
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function scopeNew($query)
    {
        return $query->whereBetween('published_at', [now()->copy()->subWeek(), now()]);
    }

    public function scopeExceptNew($query)
    {
        return $query->whereNotBetween('published_at', [now()->copy()->subWeek(), now()]);
    }
}
