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

    public function getContentAttribute()
    {
        return $this->event;
    }

    public function getStartAttribute()
    {
        return $this->year . '-01-01';
    }

    public function getCenturyAttribute()
    {
        return substr($this->year, 0, 2) . '00';
    }

    public function scopeGenerate($query, $limit = null)
    {
        $events = [];

        foreach (Timeline::all() as $event) {
            array_push($events, [
                'year' => $event->year, 
                'content' => $event->event, 
                'start' => $event->year . '01-01', 
                'end' => $event->year . '02-01']);
        }

        foreach (Composer::famous()->get() as $composer) {
            array_push($events, [
                'year' => $composer->born_in, 
                'content' => $composer->name . ' was born.', 
                'start' => $composer->born_in . '-02-01', 
                'end' => $composer->born_in . '-02-01']);
        }

        foreach (Composer::famous()->get() as $composer) {
            array_push($events, [
                'year' => $composer->died_in, 
                'content' => $composer->name . ' died.', 
                'start' => $composer->died_in . '-02-01',
                'end' => $composer->died_in . '-02-01']);
        }
        
        usort($events, function($a, $b) {
            return $a["year"] - $b["year"];
        });

        foreach ($events as $index => $event) {
            $events[$index]['id'] = $index + 1;
        }

        return $events;        
    }

    public function scopeFor($query, $pieceId, $limit = null)
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

        if ($limit) {
            $events = $this->trimBefore($events, $limit);
            $events = $this->trimAfter($events, $limit);
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
