<?php

namespace App;

class Location extends PianoLit
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCreateIfNotExists($query, $userId, $data)
    {
    	if (! $this->find($userId))
    		$this->create($data);

    	return $this->find($userId);
    }

    public function getCountryFlagAttribute()
    {
        return '<span class="flag-icon flag-icon-'.strtolower($this->countryCode).' rounded-sm shadow-center mx-1"></span>';
    }

    public function getFullLocationAttribute()
    {
    	return $this->cityName . ', ' . $this->regionName . '(' . $this->countryName . ')';
    }

    public function getCoordinatesAttribute()
    {
    	return $this->latitude . ', ' . $this->longitude;
    }

    public function getGooglemapAttribute()
    {
        return "http://maps.google.com/?q={$this->coordinates}&ll={$this->coordinates}&z=12";
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('countryName', $country);
    }

    public function scopeDatatable($query)
    {
        return datatable($query)->withBlade([
            'user' => view('admin.pages.users.locations.table.user'),
            'action' => view('admin.pages.users.locations.table.actions')
        ])->make();
    }
}
