<?php

namespace App;

class MetaverseLocation extends PianoLit
{
    public function getIconAttribute()
    {
        return asset('images/icons/metaverse/' . str_slug($this->name) . '.svg');
    }

    public function getFormattedCapacityAttribute()
    {
        if (! $this->capacity)
            return 'Unlimited';

        return $this->capacity . ' people';
    }

    public function scopeDatatable($query)
    {
        return datatable($query)->withBlade([
            'name' => view('admin.pages.metaverse.locations.table.name'),
            'capacity' => view('admin.pages.metaverse.locations.table.capacity'),
            'actions' => view('admin.pages.metaverse.locations.table.actions'),
        ])->make();
    }
}
