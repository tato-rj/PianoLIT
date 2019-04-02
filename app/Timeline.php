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

        $extraPiece = Piece::where(function($q) use ($mainPiece) {
                            $q->where('catalogue_name', '!=', $mainPiece->catalogue_name)->where('catalogue_number', '!=', $mainPiece->catalogue_number);
                        })
                       ->whereBetween('composed_in', [$minYear, $maxYear])
                       ->inRandomOrder()
                       ->first();

        $events = collect();

        $events->push(['year' => $mainPiece->composed_in, 'event' => $mainPiece->timeline_name . ' was composed by ' . $mainPiece->composer->short_name . '.', 'highlight' => true]);

        if ($extraPiece)
   		   $events->push(['year' => $extraPiece->composed_in, 'event' => $extraPiece->timeline_name . ' was composed by ' . $extraPiece->composer->short_name . '.', 'highlight' => false]);

    	foreach (Timeline::whereBetween('year', [$minYear, $maxYear])->get() as $event) {
    		$events->push(['year' => $event->year, 'event' => $event->event, 'highlight' => false]);
    	}

    	return $events->sortBy('year')->values();
    }
}
