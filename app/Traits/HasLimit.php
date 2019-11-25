<?php

namespace App\Traits;

trait HasLimit
{
    public function scopeAtLeast($query, $limit, $model = 'pieces')
    {
        return $query->has($model, '>=', production() ? $limit : 2);
    }
}
