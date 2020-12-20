<?php

namespace App;

use App\Merchandise\Product;
use App\Behaviors\PublishableContent;

class Review extends PublishableContent
{	
    protected $casts = ['anonymous' => 'boolean'];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function reviewable()
	{
		return $this->morphTo();
	}

	public function isFake()
	{
		return ! $this->user()->exists();
	}

	public function isAnonymous()
	{
		return (bool) $this->reviewer;
	}

	public function scopeBy($query, User $user)
	{
		return $query->where('user_id', '=', $user->id);
	}

	public function scopeFor($query, Product $product)
	{
		return $query->where(['reviewable_type' => get_class($product), 'reviewable_id' => $product->id]);
	}

	public function scopeByRating($query, $rating)
	{
		return $query->where('rating', $rating);
	}

	public function scopeRatings($query)
	{		
		return number_format($query->avg('rating'), 1);
	}

	public function scopeWithContent($query)
	{
		return $query->whereNotNull('title')->orWhereNotNull('content');
	}

	public function hasContent()
	{
		return $this->title || $this->content;
	}

    public function scopeDatatable($query)
    {
        return datatable($query->with(['user', 'reviewable']))->withDate(['created_at'])->withBlade([
            'reviewable' => view('admin.pages.reviews.table.reviewable'),
            'user' => view('admin.pages.reviews.table.users'),
            'rating' => view('admin.pages.reviews.table.rating'),
            'published' => view('admin.pages.reviews.table.published'),
            'action' => view('admin.pages.reviews.table.actions')
        ])->make();
    }
}
