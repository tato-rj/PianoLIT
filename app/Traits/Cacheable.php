<?php

namespace App\Traits;

trait Cacheable
{
    public function scopeCached($query, $duration, $method, $count = null)
    {
        if (! method_exists(\Illuminate\Database\Eloquent\Builder::class, $method))
            abort(405, 'The method ' . $method . ' is not allowed for ' . get_class($this));

        return \Cache::remember($this->methodToKey($method), $duration, function() use ($query, $method, $count) {
            if ($count)
                return $query->$method($count);

            return $query->$method();
        });        
    }

    public function methodToKey($method)
    {
        return str_replace('\\', '.', strtolower(get_class($this))) . '.' . $method;
    }
}
