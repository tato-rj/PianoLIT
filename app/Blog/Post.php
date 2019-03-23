<?php

namespace App\Blog;

use App\{PianoLit, Admin};

class Post extends PianoLit
{
    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }
}
