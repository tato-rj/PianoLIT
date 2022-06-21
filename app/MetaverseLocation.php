<?php

namespace App;

class MetaverseLocation extends PianoLit
{
    public function getIconAttribute()
    {
        return asset('images/icons/metaverse/' . str_slug($this->name) . '.svg');
    }

    public function scopeDatatable($query)
    {
        return datatable($query)->withBlade([
            'name' => view('admin.pages.metaverse.locations.table.name'),
            'actions' => view('admin.pages.metaverse.locations.table.actions'),
        ])->make();
    }
}
