<?php

namespace App\Traits;

trait ManageDates
{
    public function scopeUpUntilLastWeek($query)
    {
        return $query->where('created_at','<=', now()->subWeek());
    }

    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}