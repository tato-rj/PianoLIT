<?php

namespace App\Infograph;

use App\Traits\FindBySlug;
use App\{PianoLit, Admin};

class Topic extends PianoLit
{
	use FindBySlug;

    protected $table = 'infograph_topics';

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function infographs()
    {
        return $this->belongsToMany(Infograph::class, 'infograph_infograph_topic');
    }
}
