<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FreePick extends Model
{
    protected $guarded = [];
    protected $dates = ['scheduled_at', 'activated_at', 'retired_at'];

    protected static function booted()
    {
        static::saved(function () { app(\App\Services\FreePickSchedule::class)->forgetCurrent(); });
        static::deleted(function () { app(\App\Services\FreePickSchedule::class)->forgetCurrent(); });
    }

    public function getScheduledAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value, 'UTC') : null;
    }

    public function getActivatedAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value, 'UTC') : null;
    }

    public function getRetiredAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value, 'UTC') : null;
    }

    public function piece()
    {
        return $this->belongsTo(Piece::class);
    }

    public function scopeCurrent($query)
    {
        return $query->whereNull('retired_at')->where('scheduled_at', '<=', now('UTC'))
            ->orderByDesc('scheduled_at')->orderByDesc('id');
    }
}
