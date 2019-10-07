<?php

namespace App;

class Timeline extends PianoLit
{
	protected $range = 30;
    protected $appends = ['century'];
    protected $icons = [
        'history' => ['icon' => 'globe-europe', 'color' => 'teal'],
        'literature' => ['icon' => 'book', 'color' => 'pink'],
        'music' => ['icon' => 'feather-alt', 'color' => 'indigo'],
        'premiere' => ['icon' => 'theater-masks', 'color' => 'orange'],
        'birthday' => ['icon' => 'birthday-cake', 'color' => 'yellow'],
        'deathday' => ['icon' => 'skull-crossbones', 'color' => 'grey']
    ];

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

    public function getIcon($type)
    {
        if (array_key_exists($type, $this->icons))
            return $this->icons[$type];

        return $this->icons[0];
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
                'icon' => $this->getIcon($event->type)['icon'],
                'color' => $this->getIcon($event->type)['color'],
                'url' => $event['url'],
                ]);
        }

        foreach (Composer::famous()->get() as $composer) {
            array_push($events, [
                'century' => $this->century($composer->born_in),
                'decade' => $this->decade($composer->born_in),
                'year' => $composer->born_in, 
                'event' => $composer->name . ' was born.',
                'icon' => $this->getIcon('birthday')['icon'],
                'color' => $this->getIcon('birthday')['color'],
                'url' => wiki($composer->name),
                ]);
        }

        foreach (Composer::famous()->get() as $composer) {
            array_push($events, [
                'century' => $this->century($composer->died_in),
                'decade' => $this->decade($composer->died_in),
                'year' => $composer->died_in, 
                'event' => $composer->name . ' died.',
                'icon' => $this->getIcon('deathday')['icon'],
                'color' => $this->getIcon('deathday')['color'],
                'url' => wiki($composer->name),
                ]);
        }

        usort($events, function($a, $b) {
            return $a["year"] - $b["year"];
        });

        foreach ($events as $index => $event) {
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

        $age = null;

        if ($mainPiece->composer->born_in && $mainPiece->composed_in)
            $age = ', at the age of ' . ($mainPiece->composed_in - $mainPiece->composer->born_in) . ' years old';

        $info = ['year' => $mainPiece->composed_in, 
                'event' => $mainPiece->timeline_name . ' was composed by ' . $mainPiece->composer->short_name . $age . '.', 
                'highlight' => true];

        $events = [];

        array_push($events, $info);

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

        if (count($events) == 1) {
            $events = [
                ['year' => null, 
                'event' => 'The year this piece was composed is unknown.',
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
