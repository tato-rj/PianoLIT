<?php

function create($class, $attributes = [], $amount = null)
{
    $free = $class === \App\Piece::class ? ($attributes['is_free'] ?? null) : null;
    if ($class === \App\Piece::class) unset($attributes['is_free']);
    $models = factory($class, $amount)->create($attributes);
    if ($free) foreach ($models instanceof \Illuminate\Support\Collection ? $models : [$models] as $piece) setFreePick($piece);
    return $models;
}

function make($class, $attributes = [], $amount = null)
{
	return factory($class, $amount)->make($attributes);
}

// Fixtures retain their old shorthand while creating the new schedule records.
function setFreePick(\App\Piece $piece, bool $free = true)
{
    if ($free) \App\FreePick::create(['piece_id' => $piece->id, 'scheduled_at' => now('UTC'), 'activated_at' => now('UTC')]);
    else \App\FreePick::where('piece_id', $piece->id)->whereNull('retired_at')->update(['retired_at' => now('UTC')]);
    app(\App\Services\FreePickSchedule::class)->forgetCurrent();
}
