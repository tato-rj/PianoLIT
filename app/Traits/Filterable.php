<?php

namespace App\Traits;

trait Filterable
{
    public function scopeFilter($query, $filters)
    {
        return $filters->apply($query);
    }
}
