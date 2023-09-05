<?php

namespace App;

class Performance extends PianoLit
{
    protected $dates = ['approved_at'];
    protected $appends = ['claps_sum'];
    protected $withCount = ['claps'];

    public static function boot()
    {
        parent::boot();

        self::deleting(function($performance) {
            $performance->claps()->delete();
        });
    }

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

    public function clap(User $user)
    {
        return $this->claps()
                    ->firstOrCreate(['user_id' => $user->id])
                    ->increment('count');
    }

    public function scopeOf($query, Piece $piece)
    {
        return $query->where('piece_id', $piece->id);
    }

    public function isBy(User $user)
    {
        return $this->user()->is($user);
    }

    public function scopeNotBy($query, User $user)
    {
        return $query->where('user_id', '!=', $user->id);
    }

    public function scopeLast30days($scope)
    {
        return $scope->where('created_at', '>', now()->subDays(30)->endOfDay());
    }

    public function displayName()
    {
        return $this->display_name ?? $this->user->first_name;
    }

    public function getClapsSumAttribute()
    {
        return $this->claps()->sum('count');
    }

    public function scopeProcess($query, $video)
    {
        $performance = $query->where([
            'user_id' => $video['user_id'], 'piece_id' => $video['piece_id']
        ])->firstOrFail();

        return $performance->update([
            'video_url' => $video['video_url'],
            'thumbnail_url' => $video['thumb_url'],
            'bytes' => $video['compressed_size']
        ]);
    }

    public function isApproved()
    {
        return (bool) $this->approved_at;
    }

    public function approve()
    {
        $this->update(['approved_at' => now()]);
    }

    public function disapprove()
    {
        $this->update(['approved_at' => null]);
    }

    public function scopeProcessing($query)
    {
        return $query->whereNull('video_url');
    }

    public function scopePending($query)
    {
        return $query->whereNull('approved_at')->whereNotNull('video_url');
    }

    public function scopeWaiting($query)
    {
        return $query->whereNull('approved_at');
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopeDatatable($query, $actions = null)
    {
        return datatable($query)->withDate()->withBlade([
            'piece' => view('admin.pages.performances.table.piece'),
            'composer' => view('admin.pages.performances.table.composer'),
            'user' => view('admin.pages.performances.table.user'),
            'approved' => view('admin.pages.performances.table.approved'),
            'actions' => $actions ?? view('admin.pages.performances.table.actions'),
        ])->make();
    }
}
