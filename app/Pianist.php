<?php

namespace App;

class Pianist extends Person
{
    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }
}
