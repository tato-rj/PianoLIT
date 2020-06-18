<?php

namespace App\Shop;

use App\Traits\FindBySlug;
use App\{PianoLit, Admin};

class eBook extends PianoLit
{
	use FindBySlug;

    public function topics()
    {
        return $this->belongsToMany(eBookTopic::class);
    }
}
