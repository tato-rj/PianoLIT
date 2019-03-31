<?php

namespace App;

class Timeline extends PianoLit
{
	protected $range = 10;

    protected $appends = ['century'];

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function getCenturyAttribute()
    {
        return substr($this->year, 0, 2) . '00s';
    }

    public function scopeGenerate($query, $pieceId)
    {
    	$mainPiece = Piece::findOrFail($pieceId);
    	
    	$maxYear = $mainPiece->composed_in + $this->range;
    	
    	$minYear = $mainPiece->composed_in - $this->range; 

        $extraPiece = Piece::whereBetween('composed_in', [$minYear, $maxYear])->inRandomOrder()->first();

        $events = collect();

        $events->push(['year' => $mainPiece->composed_in, 'event' => $mainPiece->shortName . ' was composed', 'highlight' => true]);

   		$events->push(['year' => $extraPiece->composed_in, 'event' => $extraPiece->shortName . ' was composed', 'highlight' => false]);

    	foreach (Timeline::whereBetween('year', [$minYear, $maxYear])->get() as $event) {
    		$events->push(['year' => $event->year, 'event' => $event->event, 'highlight' => false]);
    	}

    	return $events->sortBy('year')->values();
    }
}
