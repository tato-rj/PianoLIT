<?php

namespace App;

use App\Traits\HasLimit;

class Composer extends Person
{
    use HasLimit;
    
    protected $casts = ['is_famous' => 'boolean'];
    protected $appends = ['cover_image'];
    protected $withCount = ['pieces'];

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function pieces()
    {
    	return $this->hasMany(Piece::class);
    }

    public function calculateAge($year, $event)
    {
        if (! $year || ! $this->born_in)
            return null;

        $string = null;

        if ($event == 'composition') {

            $string = ' at the age of ' . ($year - $this->born_in) . ' years old';
        
        } else if ($event == 'publication') {
        
            if ($year <= $this->died_in) {
                $string = 'when ' . $this->short_name . ' was ' . ($year - $this->born_in) . ' years old';
            } else {
                $string = ($year - $this->died_in) . ' years after ' . $this->short_name . ' died';
            }
        
        }

        return $string;
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
        return storage($this->cover_path);
    }

    public function getBackgroundAttribute()
    {
        return storage($this->cover_path);
    }
}
