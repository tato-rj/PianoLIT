<?php

namespace App;

use App\Traits\FindBySlug;

class Clip extends PianoLit
{
	use FindBySlug;

	public function getViewAttribute()
	{
		return route('clips.show', $this);
	}

    public function scopeDatatable($query)
    {
        return datatable($query)->withBlade([
            'url' => view('admin.pages.clips.table.url'),
            'actions' => view('admin.pages.clips.table.actions'),
        ])->make();
    }
}
