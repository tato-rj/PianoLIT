<?php

namespace App\Blog;

use App\Traits\FindBySlug;
use App\{PianoLit, Admin};

class Topic extends PianoLit
{
    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }
}
