<?php

namespace App\Billing;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    public function getRouteKeyName()
    {
        return 'name';
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', $name)->first();
    }

    public function other()
    {
        return Plan::where('name', '!=', $this->name)->first();
    }

    public function getLongNameAttribute()
    {
    	return ucfirst($this->name) . ' Plan';
    }

    public function formattedPrice()
    {
    	return $this->price / 100;
    }

    public function formattedMonthlyPrice()
    {
    	return $this->name == 'monthly' ? null : floor(($this->formattedPrice() / 12) * 100) / 100;
    }
}
