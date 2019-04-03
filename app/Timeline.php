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
        return substr($this->year, 0, 2) . '00';
    }

    public function scopeGenerate($query, $pieceId)
    {
    	$mainPiece = Piece::findOrFail($pieceId);
    	
    	$maxYear = $mainPiece->composed_in + $this->range;
    	
    	$minYear = $mainPiece->composed_in - $this->range; 

        $events = collect();

        $events->push(['year' => $mainPiece->composed_in, 'event' => $mainPiece->timeline_name . ' was composed by ' . $mainPiece->composer->short_name . '.', 'highlight' => true]);

    	foreach (Timeline::whereBetween('year', [$minYear, $maxYear])->get() as $event) {
    		$events->push(['year' => $event->year, 'event' => $event->event, 'highlight' => false]);
    	}

        foreach (Composer::famous()->bornBetween([$minYear, $maxYear])->get() as $composer) {
            $events->push(['year' => $composer->date_of_birth->year, 'event' => $composer->name . ' was born.', 'highlight' => false]);
        }

        foreach (Composer::famous()->diedBetween([$minYear, $maxYear])->get() as $composer) {
            $events->push(['year' => $composer->date_of_death->year, 'event' => $composer->name . ' died.', 'highlight' => false]);
        }

    	return $events->sortBy('year')->values();
    }
}
