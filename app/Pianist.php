<?php

namespace App;

use App\Traits\FindBySlug;

class Pianist extends Person
{
	use FindBySlug;
	
	protected $appends = ['nationality'];

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function scopeDatatable($query)
    {
        return datatable($query)->withBlade([
            'name' => view('admin.pages.pianists.table.name'),
            'actions' => view('admin.pages.pianists.table.actions'),
        ])->make();
    }
}
