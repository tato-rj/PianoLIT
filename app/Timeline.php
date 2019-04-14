<?php

namespace App;

class Timeline extends PianoLit
{
	protected $range = 30;

    protected $appends = ['century'];

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function getCenturyAttribute()
    {
        return substr($this->year, 0, 2) . '00';
    }

    public function scopeGenerate($query, $pieceId, $limit = null)
    {
    	$mainPiece = Piece::findOrFail($pieceId);
    	
    	$maxYear = $mainPiece->composed_in + $this->range;
    	
    	$minYear = $mainPiece->composed_in - $this->range; 

        $events = [];

        array_push($events, ['year' => $mainPiece->composed_in, 'event' => $mainPiece->timeline_name . ' was composed by ' . $mainPiece->composer->short_name . '.', 'highlight' => true]);

    	foreach (Timeline::whereBetween('year', [$minYear, $maxYear])->get() as $event) {
    		array_push($events, ['year' => $event->year, 'event' => $event->event, 'highlight' => false]);
    	}

        foreach (Composer::famous()->bornBetween([$minYear, $maxYear])->get() as $composer) {
            array_push($events, ['year' => $composer->born_in, 'event' => $composer->name . ' was born.', 'highlight' => false]);
        }

        foreach (Composer::famous()->diedBetween([$minYear, $maxYear])->get() as $composer) {
            array_push($events, ['year' => $composer->died_in, 'event' => $composer->name . ' died.', 'highlight' => false]);
        }
        
        usort($events, function($a, $b) {
            return $a["year"] - $b["year"];
        });

        $key = $this->findPieceKey($events);

        $this->trimBefore($key, $events, $limit);

        $this->trimAfter($key, $events, $limit);

    	return [];
    }

    public function findPieceKey($events)
    {
        foreach($events as $key => $event)
        {
            if ($event['highlight'] === true)
                return $key;
        }
    }

    public function trimBefore($key, $events, $limit)
    {
        $count = count($events) - 1;
        $diff = $index - $limit;

        if ($diff > 0)

    }

    public function trimAfter($key, $events, $limit)
    {
        $count = count($events) - 1;
    }
}
