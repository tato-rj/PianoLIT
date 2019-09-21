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

    public function century($year)
    {
        return intval(substr($year, 0, 2)) + 1 . 'th';
    }

    public function decade($year)
    {
        return floor($year/10) * 10;        
    }

    public function scopeGenerate($query, $limit = null)
    {
        $events = $decades = $centuries = [];

        foreach (Timeline::all() as $event) {
            array_push($events, [
                'century' => $this->century($event->year),
                'decade' => $this->decade($event->year),
                'year' => $event->year,
                'event' => $event->event,
                'icon' => $event->type == 'music' ? 'feather-alt' : 'globe-europe',
                'color' => $event->type == 'music' ? 'indigo' : 'teal'
                ]);
        }

        foreach (Composer::famous()->get() as $composer) {
            array_push($events, [
                'century' => $this->century($composer->born_in),
                'decade' => $this->decade($composer->born_in),
                'year' => $composer->born_in, 
                'event' => $composer->name . ' was born.',
                'icon' => 'birthday-cake',
                'color' => 'yellow'
                ]);
        }

        foreach (Composer::famous()->get() as $composer) {
            array_push($events, [
                'century' => $this->century($composer->died_in),
                'decade' => $this->decade($composer->died_in),
                'year' => $composer->died_in, 
                'event' => $composer->name . ' died.',
                'icon' => 'skull-crossbones',
                'color' => 'grey'
                ]);
        }

        usort($events, function($a, $b) {
            return $a["year"] - $b["year"];
        });

        foreach ($events as $event) {
            $centuries[$event['century']][] = $event;
        }

        foreach ($centuries as $century => $events) {
            foreach ($events as $event) {
                $decades[$century][$event['decade']][] = $event;
            }
        }

        return $decades;        
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
