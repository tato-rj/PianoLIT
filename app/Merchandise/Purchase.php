<?php

namespace App\Merchandise;

use App\PianoLit;
use App\Events\PurchaseMade;

class Purchase extends PianoLit
{
    protected static function boot()
    {
        parent::boot();

        self::created(function($item) {
            event(new PurchaseMade($item));
        });
    }

    public function item()
    {
        return $this->morphTo();
    }

    public function getIsFreeAttribute()
    {
        return $this->item->isFree();
    }

    public function scopeFree($query)
    {
    	return $query->whereNull('cost');
    }

    public function scopePaid($query)
    {
    	return $query->whereNotNull('cost');
    }
}
