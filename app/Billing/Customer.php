<?php

namespace App\Billing;

use App\{PianoLit, User};

class Customer extends PianoLit
{
    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    public function scopeByCustomerId($query, $customerId)
    {
    	return $query->where('stripe_id', $customerId)->firstOrFail();
    }

    public function hasCard()
    {
    	return $this->card_last_four && $this->card_brand;
    }

    public function card()
    {
        if ($this->hasCard())
            return ucfirst($this->card_brand) . ' &middot;&middot;&middot;&middot; ' . $this->card_last_four;

        return null;
    }
}
