<?php

namespace App\Traits;

trait ManageDates
{
    public function scopeUpUntilLastWeek($query)
    {
        return $query->where('created_at','<=', now()->subWeek());
    }
}