<?php

namespace App\Games\Duels;

use Illuminate\Database\Eloquent\Model;

class Duel extends Model
{
    public const WAITING = 'waiting_for_opponent';

    public const READY = 'ready';

    public const COUNTDOWN = 'countdown';

    public const PLAYING = 'playing';

    public const FINISHED = 'finished';

    public const CANCELLED = 'cancelled';

    public const EXPIRED = 'expired';

    protected $table = 'game_duels';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['settings' => 'array', 'starts_at' => 'datetime', 'expires_at' => 'datetime', 'finished_at' => 'datetime'];

    public function players()
    {
        return $this->hasMany(DuelPlayer::class);
    }
}
