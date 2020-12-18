<?php

namespace App\Merchandise;

use App\{ShareableContent, Review};
use App\Traits\Filterable;

abstract class Product extends ShareableContent
{
	use Filterable;

	public function reviews()
	{
		return $this->morphMany(Review::class, 'reviewable');
	}

	public function reviewRoute($rating, $title = null, $content = null)
	{
		return route('reviews.store', ['model' => get_class($this), 'id' => $this->id, 'rating' => $rating, 'title' => $title, 'content' => $content]);
	}

	public function purchaseRoute()
	{
		return route('shop.purchase', ['model' => get_class($this), 'reference' => $this]);
	}

	public function getProductNameAttribute()
	{
		return class_basename($this);
	}

	public function generateKeywords()
	{
        $title = $this->title . ' sheet music';

        $composer = $this->piece()->exists() ? $this->piece->composer->name . ' sheet music' : null;

        $words = array_values(array_filter([$title, $composer]));

        return arrayToSentence($words, ',', ',') . ',scores,sheet music';
	}
}
