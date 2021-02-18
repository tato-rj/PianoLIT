<?php

namespace App\Billing;

use App\{PianoLit, User};
use App\Merchandise\Purchase;

class LoyaltyDiscount extends PianoLit
{
	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function purchase()
	{
		return $this->belongsTo(Purchase::class);
	}

	public function scopeApply($query, Purchase $purchase, $discount)
	{
		$this->create([
			'purchase_id' => $purchase->id,
			'discount' => $discount
		]);
	}

	public function scopeLastMonth($query)
	{
		return $query->whereDate('loyalty_discounts.created_at', '>', now()->subMonth());
	}

	public function availableIn()
	{
		return $this->created_at->addMonth()->diffForHumans();
	}
}
