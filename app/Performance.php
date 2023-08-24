<?php

namespace App;

class Performance extends PianoLit
{
    protected $dates = ['approved_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function piece()
    {
        return $this->belongsTo(Piece::class);
    }

    public function claps()
    {
        return $this->hasMany(Clap::class);
    }

    public function clap()
    {
        return $this->claps()
                    ->firstOrCreate(['user_id' => auth()->user()->id])
                    ->increment('count');
    }

    public function process($url)
    {
        $ext = pathinfo($url, PATHINFO_EXTENSION);

        $thumbnail_url = str_replace($ext, 'jpg', $url);

        $this->update([
            'video_url' => $url,
            'thumbnail_url' => $thumbnail_url
        ]);
    }

    public function approve()
    {
        $this->update(['approved_at' => now()]);
    }

    public function scopeByPublicId($query, $publicId)
    {
        return $query->where('public_id', $publicId);
    }

    public function scopeProcessing($query)
    {
        return $query->whereNull('video_url');
    }

    public function scopePending($query)
    {
        return $query->whereNull('approved_at')->whereNotNull('video_url');
    }

    public function scopeConfirmed($query)
    {
        return $query->whereNotNull('approved_at');
    }
}
