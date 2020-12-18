<?php

namespace App;

use App\Merchandise\Product;
use App\Behaviors\PublishableContent;

class Review extends PublishableContent
{	
	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function scopeBy($query, User $user)
	{
		return $query->where('user_id', '=', $user->id);
	}

	public function scopeFor($query, Product $product)
	{
		return $query->where(['reviewable_type' => get_class($product), 'reviewable_id' => $product->id]);
	}

	public function scopeRatings($query)
	{
		$rating = $query->avg('rating');
		$round = number_format($rating);

		// IS ROUND NUMBER
		if (! is_decimal($rating))
			return $round;

		// IS CLOSER TO TOP NUMBER
		if (ceil($rating) == $round)
			return $round;

		return $round + .5;
	}
}
