<?php

namespace App\Games\Duels;

use Illuminate\Database\Eloquent\Model;

class DuelPlayer extends Model
{
    protected $table = 'game_duel_players';

    protected $guarded = [];

    protected $hidden = ['token_hash', 'connection_id'];

    protected $casts = ['ready_at' => 'datetime', 'rematch_at' => 'datetime', 'finished_at' => 'datetime', 'last_seen_at' => 'datetime', 'departed_at' => 'datetime', 'answered_at' => 'datetime', 'answer_correct' => 'boolean', 'result' => 'array', 'checkpoint' => 'array'];
}
