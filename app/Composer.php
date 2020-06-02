<?php

namespace App;

use App\Traits\HasLimit;

class Composer extends Person
{
    use HasLimit;
    
    protected $casts = ['is_famous' => 'boolean', 'is_pedagogical' => 'boolean'];
    protected $appends = ['cover_image', 'alive_on', 'short_name', 'born_at', 'died_at', 'lifespan'];
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
        
            if ($year <= $this->died_in || ! $this->died_in) {
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

    public function scopeName($query, $name)
    {
        return $query->where('name', $name);
    }

    public function getPeriodAttribute($period)
    {
        return ucfirst($period);
    }

    public function scopeFamous($query)
    {
        return $query->where('is_famous', true);
    }

    public function scopeNonPedagogical($query)
    {
        return $query->where('is_pedagogical', false);        
    }

    public function getCoverImageAttribute()
    {
        return storage($this->cover_path);
    }

    public function getBackgroundAttribute()
    {
        return storage($this->cover_path);
    }

    public function scopeDatatable($query)
    {
        return datatable($query)->withDate()->withBlade([
            'name' => view('admin.pages.composers.table.name'),
            'famous' => view('admin.pages.composers.table.famous'),
            'actions' => view('admin.pages.composers.table.actions'),
        ])->make();
    }
}
