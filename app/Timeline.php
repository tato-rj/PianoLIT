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

    public function scopeFor($query, $pieceId, $limit = null)
    {
        $mainPiece = $pieceId instanceof Piece ? $pieceId : Piece::findOrFail($pieceId);
        
        $originalYear = $mainPiece->composed_in ? $mainPiece->composed_in : $mainPiece->published_in;

    	$maxYear = $originalYear + $this->range;
    	
    	$minYear = $originalYear - $this->range;

        $info = ['year' => $originalYear, 
                'event' => $mainPiece->timeline_name . $mainPiece->original_event, 
                'highlight' => true];

        $events = [];

        array_push($events, $info);

    	foreach (Timeline::whereBetween('year', [$minYear, $maxYear])->get() as $event) {
    		array_push($events, ['year' => $event->year, 'event' => $event->event, 'highlight' => false]);
    	}

        foreach (Composer::without('country')->famous()->bornBetween([$minYear, $maxYear])->get() as $composer) {
            array_push($events, ['year' => $composer->born_in, 'event' => $composer->name . ' was born.', 'highlight' => false]);
        }

        foreach (Composer::without('country')->famous()->diedBetween([$minYear, $maxYear])->get() as $composer) {
            array_push($events, ['year' => $composer->died_in, 'event' => $composer->name . ' died.', 'highlight' => false]);
        }
        
        usort($events, function($a, $b) {
            return $a["year"] - $b["year"];
        });

        if ($limit) {
            $events = $this->trimBefore($events, $limit);
            $events = $this->trimAfter($events, $limit);
        }

        if (count($events) == 1 && ! $events[0]['year']) {
            $events = [
                ['year' => null, 
                'event' => 'It is unknown which year this piece was composed.',
                'highlight' => false]
            ];
        }

    	return $events;
    }

    public function findPieceKey($events)
    {
        foreach($events as $key => $event)
        {
            if ($event['highlight'] === true)
                return $key;
        }
    }

    public function trimBefore($events, $limit)
    {
        $array = $events;
        $key = $this->findPieceKey($events);
        $diff = $key - $limit;

        if ($diff > 0) {
            for ($i=0; $i<$diff; $i++) {
                array_shift($array);
            }
        }

        return $array;
    }

    public function trimAfter($events, $limit)
    {
        $array = $events;
        $key = $this->findPieceKey($events);
        $count = count($events) - 1;
        $diff = ($count - $key) - $limit;

        if ($diff > 0) {
            for ($i=0; $i<$diff; $i++) {
                array_pop($array);
            }
        }

        return $array;
    }
}
