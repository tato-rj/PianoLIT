<?php

namespace App;

abstract class Person extends PianoLit
{
    protected $appends = ['last_name', 'short_name'];
    protected $dates = ['date_of_birth', 'date_of_death'];
    protected $with = ['country'];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function wasBornToday()
    {
        return $this->date_of_birth->isBirthday(now());
    }

    public function hasDiedToday()
    {
        return $this->date_of_death->isBirthday(now());
    }

    public function scopeBornToday($query)
    {
        return $query->whereRaw('DATE_ADD(date_of_birth, INTERVAL YEAR(CURDATE())-YEAR(date_of_birth) + IF(DAYOFYEAR(CURDATE()) > DAYOFYEAR(date_of_birth),1,0) YEAR) = CURDATE()');
    }

    public function scopeUpcomingBirthdays($query, $days)
    {
        return $query->whereRaw('
            DATE_ADD(date_of_birth, INTERVAL YEAR(CURDATE())-YEAR(date_of_birth) + IF(DAYOFYEAR(CURDATE()) > DAYOFYEAR(date_of_birth),1,0) YEAR) 
            BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL '.$days.' DAY)')->orderByRaw('MONTH(date_of_birth), DAY(date_of_birth)');
    }

    public function scopeDiedToday($query)
    {
        return $query->whereRaw('DATE_ADD(date_of_death, INTERVAL YEAR(CURDATE())-YEAR(date_of_death) + IF(DAYOFYEAR(CURDATE()) > DAYOFYEAR(date_of_death),1,0) YEAR) = CURDATE()');
    }

    public function scopeUpcomingDeathdays($query, $days)
    {
        return $query->whereRaw('
            DATE_ADD(date_of_death, INTERVAL YEAR(CURDATE())-YEAR(date_of_death) + IF(DAYOFYEAR(CURDATE()) > DAYOFYEAR(date_of_death),1,0) YEAR) 
            BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL '.$days.' DAY)')->orderByRaw('MONTH(date_of_birth), DAY(date_of_birth)');
    }

    public function getBornInAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->year : null;
    }

    public function getDiedInAttribute()
    {
        return $this->date_of_death ? $this->date_of_death->year : null;
    }

    public function getAgeAttribute()
    {
        if (! $this->date_of_birth)
            return null;

        $date = $this->date_of_death ?? now();

        return $date->diffInYears($this->date_of_birth);
    }

    public function getNationalityAttribute()
    {
        return $this->country->nationality;
    }

    public function getBornAtAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->toFormattedDateString() : null;
    }

    public function getDiedAtAttribute()
    {
        return $this->date_of_death ? $this->date_of_death->toFormattedDateString() : null;
    }

    public function getAliveOnAttribute()
    {
        if (! $this->date_of_birth)
            return null;

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
}
