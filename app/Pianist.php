<?php

namespace App;

use App\Traits\FindBySlug;

class Pianist extends Person
{
	use FindBySlug;
	
    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }
}
