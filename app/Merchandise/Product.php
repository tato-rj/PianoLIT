<?php

namespace App\Merchandise;

use App\{ShareableContent, Review};
use App\Traits\{Filterable, HasMockup};

abstract class Product extends ShareableContent
{
	use Filterable, HasMockup;

	protected $withCount = ['reviews'];

	public function reviews()
	{
		return $this->morphMany(Review::class, 'reviewable');
	}

    public function isFree()
    {
        return $this->price == 0 || $this->discount == 100;
    }

    public function hasDiscount()
    {
    	return (bool) $this->discount;
    }

	public function publishedReviews()
	{
		return $this->morphMany(Review::class, 'reviewable')->published();
	}

	public function reviewRoute($prefix = null)
	{
		$route = $prefix ? $prefix . '.reviews.store' : 'reviews.store';
		
		return route($route, ['model' => get_class($this), 'id' => $this->id]);
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
