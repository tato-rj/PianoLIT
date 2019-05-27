<?php

namespace App;

class Composer extends PianoLit
{
    protected $dates = ['date_of_birth', 'date_of_death'];
    protected $appends = ['last_name', 'short_name', 'nationality_name'];
    protected $casts = ['is_famous' => 'boolear'];
    protected $withCount = ['pieces'];

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function pieces()
    {
    	return $this->hasMany(Piece::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function getBornInAttribute()
    {
        return $this->date_of_birth->year;
    }

    public function getDiedInAttribute()
    {
        return $this->date_of_death ? $this->date_of_death->year : null;
    }

    public function getNationalityNameAttribute()
    {
        return $this->country->nationality;
    }

    public function getPeriodAttribute($period)
    {
        return ucfirst($period);
    }

    public function getBornAtAttribute()
    {
        return $this->date_of_birth->toFormattedDateString();
    }

    public function getDiedAtAttribute()
    {
        return $this->date_of_death ? $this->date_of_death->toFormattedDateString() : null;
    }

    public function getAliveOnAttribute()
    {
        if ($this->date_of_death)
            return "{$this->date_of_birth->year} - {$this->date_of_death->year}";

        return "{$this->date_of_birth->year} - now";
    }

    public function getShortNameAttribute()
    {
        $namesArray = explode(' ', $this->name);

        $initials = '';

        $lastName = array_pop($namesArray);

        foreach ($namesArray as $name) {
            $initials .= $name[0].'.';
        }

        return "$initials $lastName";
    }

    public function getLastNameAttribute()
    {
        return splitname($this->name)['last'];
    }

    public function scopeBornBetween($query, $years)
    {
        return $query->whereYear('date_of_birth', '>=', $years[0])->whereYear('date_of_birth', '<=', $years[1]);
    }

    public function scopeDiedBetween($query, $years)
    {
        return $query->whereYear('date_of_death', '>=', $years[0])->whereYear('date_of_death', '<=', $years[1]);
    }

    public function scopeFamous($query)
    {
        return $query->where('is_famous', true);
    }
}
