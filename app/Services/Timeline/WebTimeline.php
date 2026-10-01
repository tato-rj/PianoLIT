<?php

namespace App\Services\Timeline;

use App\Piece;

class WebTimeline
{
    public function forPiece(Piece $piece)
    {
        $events = $piece->timelineEvents()->chronological()->get()->map(function ($event) {
            return array_merge($event->getAttributes(), ['highlight' => false]);
        });

        $composed = $this->usableYear($piece->composed_in);
        $year = $composed ?: $this->usableYear($piece->published_in);
        if ($year) {
            $events->push([
                'year' => $year,
                'event_date' => null,
                'title' => $piece->timeline_name . ($composed ? ' was composed' : ' was published'),
                'description' => $piece->composer->name,
                'image_url' => null,
                'source_url' => null,
                'highlight' => true,
            ]);
        }

        return $events->sortBy(function ($event) {
            return $event['event_date'] ?: sprintf('%04d-01-01', $event['year']);
        })->values();
    }

    private function usableYear($year)
    {
        return is_numeric($year) && $year >= 1 && $year <= 9999 ? (int) $year : null;
    }
}
