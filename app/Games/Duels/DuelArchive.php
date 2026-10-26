<?php

namespace App\Games\Duels;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class DuelArchive extends Model
{
    protected $table = 'game_duel_archives';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['snapshot' => 'array', 'played_at' => 'datetime'];

    public function recordedDuel(): Duel
    {
        $duel = new Duel(Arr::except($this->snapshot, ['players']));
        $duel->setRelation('players', new Collection(array_map(
            fn ($player) => new DuelPlayer($player), $this->snapshot['players'] ?? []
        )));

        return $duel;
    }
}
