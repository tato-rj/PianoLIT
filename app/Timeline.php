<?php

namespace App;

class Timeline extends PianoLit
{
	protected $range = 20;

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function scopeGenerate($query, $pieceId)
    {
    	$mainPiece = Piece::findOrFail($pieceId);

    	$events = collect();
    	
    	$maxYear = $mainPiece->composed_in + $this->range;
    	
    	$minYear = $mainPiece->composed_in - $this->range; 
   	
    	foreach (Piece::whereBetween('composed_in', [$minYear, $maxYear])->get() as $piece) {
    		$events->push(['year' => $piece->composed_in, 'event' => $piece->shortName . ' was composed', 'highlight' => $piece->composed_in == $mainPiece->composed_in]);
    	}

    	foreach (Timeline::whereBetween('year', [$minYear, $maxYear])->get() as $event) {
    		$events->push(['year' => $event->year, 'event' => $event->event, 'highlight' => false]);
    	}

    	return $events->sortBy('year');
    }
}
