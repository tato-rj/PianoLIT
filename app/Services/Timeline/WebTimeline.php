<?php

namespace App\Services\Timeline;

use App\{Piece, TimelineEvent};

class WebTimeline
{
    protected $maxEvents = 10;

    public function periodForPiece(Piece $piece): ?array
    {
        $composer = $piece->composer;
        if (!$composer) return null;
        $born = $this->usableYear($composer->born_in);
        $died = $this->usableYear($composer->died_in);
        if (!$born && !$died) return null;
        if (($born && $composer->date_of_birth->isFuture()) || ($died && $composer->date_of_death->isFuture())) return null;
        if ($born && $died && $composer->date_of_death->lt($composer->date_of_birth)) return null;

        $span = config('webapp.timeline_partial_lifespan_years', 50);
        return [
            'born' => $born,
            'died' => $died,
            'start_year' => $born ?: max(1, $died - $span),
            'end_year' => $died ?: min(9999, $born + $span),
        ];
    }

    public function forPiece(Piece $piece)
    {
        $period = $this->periodForPiece($piece);
        if (!$period) return collect();
        $composer = $piece->composer;
        $born = $period['born'];
        $died = $period['died'];
        $query = TimelineEvent::whereBetween('year', [$period['start_year'], min(now()->year, $period['end_year'])]);
        $start = $born ? $composer->date_of_birth->toDateString() : sprintf('%04d-01-01', $period['start_year']);
        $end = $died ? $composer->date_of_death->toDateString() : sprintf('%04d-12-31', min(now()->year, $period['end_year']));
        if ($end > now()->toDateString()) $end = now()->toDateString();
        // Year-only events retain their approximate precision at the boundaries.
        $query->where(function ($query) use ($start) {
            $query->whereNull('event_date')->orWhere('event_date', '>=', $start);
        })->where(function ($query) use ($end) {
            $query->whereNull('event_date')->orWhere('event_date', '<=', $end);
        });

        $milestones = $this->pieceMilestones($piece, $born, $died);
        $slots = $this->maxEvents - ($born ? 1 : 0) - ($died ? 1 : 0) - $milestones->count();
        // Select lightweight dates across the whole composer period, then fetch curated content.
        $dates = $query->chronological()->get(['id', 'year', 'event_date']);
        $selected = $this->spreadAcrossLifetime($dates, $slots);
        $events = $selected->isEmpty() ? collect() : TimelineEvent::whereKey($selected->pluck('id'))->chronological()->get()->map(function ($event) {
            return array_merge($event->getAttributes(), ['highlight' => false]);
        });
        $events = $events->concat($milestones);
        if ($born) $events->push($this->composerMilestone($composer, $born, 'birth'));
        if ($died) $events->push($this->composerMilestone($composer, $died, 'death'));

        return $events->sortBy(function ($event) {
            // Birth/death frame approximate events in their years. Posthumous piece dates remain chronological.
            $rank = ($event['composer_milestone'] ?? null) === 'birth' ? 0 : (($event['composer_milestone'] ?? null) === 'death' ? 2 : 1);
            return sprintf('%04d-%d-%s', $event['year'], $rank, $event['event_date'] ?: '0000-01-01');
        })->values();
    }

    private function pieceMilestones(Piece $piece, ?int $born, ?int $died)
    {
        $dates = collect();
        foreach (['composed_in' => 'composed', 'published_in' => 'published'] as $field => $verb) {
            $year = $this->usableYear($piece->$field);
            if ($year) $dates->put($year, array_merge($dates->get($year, []), [$verb]));
        }
        return $dates->map(function ($verbs, $year) use ($piece, $born, $died) {
            $description = $piece->composer->name;
            if ($born && $year >= $born && (!$died || $year <= $died) && $year <= now()->year) {
                $age = $year - $born;
                $description .= ' was '.$age.' '.str_plural('year', $age).' old';
            }
            return [
                'year' => (int) $year, 'event_date' => null,
                'title' => $piece->timeline_name.' was '.implode(' and ', $verbs),
                'description' => $description, 'image_url' => null, 'source_url' => null, 'highlight' => true,
            ];
        })->values();
    }

    private function composerMilestone($composer, int $year, string $kind): array
    {
        return [
            'year' => $year,
            'event_date' => ($kind === 'birth' ? $composer->date_of_birth : $composer->date_of_death)->toDateString(),
            'title' => $composer->name.($kind === 'birth' ? ' was born' : ' died'),
            'description' => $kind === 'birth' ? 'Beginning of the composer’s lifetime.' : 'End of the composer’s lifetime.',
            'image_url' => null, 'source_url' => null, 'highlight' => false, 'composer_milestone' => $kind,
        ];
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
