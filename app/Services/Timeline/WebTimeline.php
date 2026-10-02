<?php

namespace App\Services\Timeline;

use App\{Piece, TimelineEvent};

class WebTimeline
{
    public function forPiece(Piece $piece)
    {
        $composed = $this->usableYear($piece->composed_in);
        $year = $composed ?: $this->usableYear($piece->published_in);
        $composer = $piece->composer;
        $born = $this->usableYear($composer->born_in);
        $died = $this->usableYear($composer->died_in);
        $limit = 8;
        $query = TimelineEvent::query();

        if ($year) {
            $range = config('webapp.timeline_year_range', 10);
            $query->whereBetween('year', [max(1, $year - $range), min(9999, $year + $range)]);
        } elseif ($born && (!$died || $died >= $born)) {
            $end = $died ?: min(now()->year, $born + config('webapp.timeline_unknown_lifespan_years', 80));
            $query->whereBetween('year', [$born, $end]);
            // Year-only events retain their approximate precision at the lifetime boundaries.
            $query->where(function ($query) use ($composer) {
                $query->whereNull('event_date')->orWhere('event_date', '>=', $composer->date_of_birth->toDateString());
            });
            if ($died) {
                $query->where(function ($query) use ($composer) {
                    $query->whereNull('event_date')->orWhere('event_date', '<=', $composer->date_of_death->toDateString());
                });
            } else {
                $query->where(function ($query) {
                    $query->whereNull('event_date')->orWhere('event_date', '<=', now()->toDateString());
                });
            }
        } else {
            return collect();
        }

        // Select using lightweight dates; fetch curated text/images only for the chosen rows.
        $dates = $query->chronological()->get(['id', 'year', 'event_date']);
        $selected = $year ? $this->aroundYear($dates, $year, $limit - 1) : $this->spreadAcrossLifetime($dates, $limit);
        $events = $selected->isEmpty() ? collect() : TimelineEvent::whereKey($selected->pluck('id'))->chronological()->get()->map(function ($event) {
            return array_merge($event->getAttributes(), ['highlight' => false]);
        });

        if ($year) {
            $description = $composer->name;
            // Piece dates have year precision, matching the existing composer age convention.
            if ($born && $year >= $born && (!$died || $year <= $died)) {
                $age = $year - $born;
                $description .= ' was '.$age.' '.str_plural('year', $age).' old';
            }
            $events->push([
                'year' => $year,
                'event_date' => null,
                'title' => $piece->timeline_name . ($composed ? ' was composed' : ' was published'),
                'description' => $description,
                'image_url' => null,
                'source_url' => null,
                'highlight' => true,
            ]);
        }

        return $events->sortBy(function ($event) {
            return $event['event_date'] ?: sprintf('%04d-01-01', $event['year']);
        })->values();
    }

    private function aroundYear($dates, int $year, int $limit)
    {
        $perSide = intdiv($limit, 2);
        $selected = $dates->filter(function ($event) use ($year) { return $event->year < $year; })->reverse()->take($perSide)
            ->concat($dates->filter(function ($event) use ($year) { return $event->year > $year; })->take($perSide))
            ->concat($dates->filter(function ($event) use ($year) { return $event->year === $year; })->take($limit % 2));
        // Fill sparse sides with the nearest remaining events, including the reference year.
        $remaining = $dates->whereNotIn('id', $selected->pluck('id'))->sortBy(function ($event) use ($year) {
            return abs($event->year - $year);
        });
        return $selected->concat($remaining->take($limit - $selected->count()))->values();
    }

    private function spreadAcrossLifetime($dates, int $limit)
    {
        if ($dates->count() <= $limit) return $dates;
        $selected = collect([$dates->first(), $dates->last()]);
        // Anchor both ends, then fill the widest gaps rather than clustering in busy years.
        while ($selected->count() < $limit) {
            $remaining = $dates->whereNotIn('id', $selected->pluck('id'));
            $next = $remaining->sortByDesc(function ($event) use ($selected) {
                return $selected->min(function ($chosen) use ($event) { return abs($chosen->year - $event->year); });
            })->first();
            $selected->push($next);
        }
        return $selected;
    }

    private function usableYear($year)
    {
        return is_numeric($year) && (float) $year === (float) (int) $year && $year >= 1 && $year <= 9999 ? (int) $year : null;
    }
}
