<?php

namespace App\Traits;

use App\Review;

trait HasProductReviews
{
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function publishedReviews()
    {
        return $this->reviews()->published();
    }

    public function reviewRoute($prefix = null)
    {
        return route($prefix ? $prefix.'.reviews.store' : 'reviews.store', ['model' => get_class($this), 'id' => $this->id]);
    }
}
