<?php

namespace App;

class Composer extends Person
{
    protected $casts = ['is_famous' => 'boolean'];
    protected $withCount = ['pieces'];

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function pieces()
    {
    	return $this->hasMany(Piece::class);
    }

    public function scopeByPeriod($query)
    {
        return \DB::table('composers')->selectRaw('period, count(*) as count')->groupBy('period')->get();
    }

    public function getPeriodAttribute($period)
    {
        return ucfirst($period);
    }

    public function scopeFamous($query)
    {
        return $query->where('is_famous', true);
    }

    public function getCoverImageAttribute()
    {
        return asset('images/composers/' . str_slug($this->short_name) . '.jpg');
    }
}
