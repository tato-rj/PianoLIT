<?php

namespace App;

class Rating extends PianoLit
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeConfirmed($query)
    {
        return $query->whereNotNull('score');
    }

    public function scopeUnconfirmed($query)
    {
        return $query->whereNull('score');
    }

    public function scopeRecently($query)
    {
        return $query->where('created_at', '>=', now()->subDays(6));
    }

    public function scopeTooMany($query)
    {
        return $query->where('attempts', '>=', 4);
    }
}
