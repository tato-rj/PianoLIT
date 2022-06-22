<?php

namespace App;

class MetaverseEvent extends PianoLit
{
    protected $durations = ['15 min', '20 min', '30 min', '45 min', '60 min'];
    protected $casts = ['date' => 'datetime'];

    public function location()
    {
        return $this->belongsTo(MetaverseLocation::class);
    }

    public function scopeSchedule($query)
    {
        return $query->orderBy('date');
    }

    public function scopeUpcoming($query)
    {
        return $query->whereDate('date', '>=', now());
    }

    public function locations()
    {
        $locations = collect();

        foreach (MetaverseLocation::all() as $location) {
            $locations->put($location->name, $location->id);
        }

        return $locations;
    }

    public function durations()
    {
        return $this->durations;
    }

    public function getFormattedDateAttribute()
    {
        return $this->date->format('D, M j');
    }

    public function getFormattedTimeAttribute()
    {
        return $this->date->format('h:i a');
    }

    public function getLocationIconAttribute()
    {
        $filename = strtolower($this->location) . '.svg';

        return asset('images/icons/metaverse/'.$filename);
    }

    public function scopeDatatable($query)
    {
        return datatable($query)->withBlade([
            'location' => view('admin.pages.metaverse.table.location'),
            'date' => view('admin.pages.metaverse.table.date'),
            'actions' => view('admin.pages.metaverse.table.actions'),
        ])->make();
    }
}
