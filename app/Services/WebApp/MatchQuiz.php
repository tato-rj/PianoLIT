<?php

namespace App\Services\WebApp;

use App\{Piece, Tag};
use App\Resources\FindYourMatch\Quiz;

/** Web-only ranking; the public funnel and mobile engines remain unchanged. */
class MatchQuiz extends Quiz
{
    public function getKeywords($input)
    {
        $ids = array_values(array_filter($input, 'is_numeric'));
        $names = array_values(array_filter($input, function ($value) { return !is_numeric($value); }));
        $this->pieces = Piece::whereIn('id', $ids)->select('pieces.*')->setEagerLoads([])->with('tags')->get();
        $keywords = Tag::whereIn('name', $names)->get();
        $this->levels = $keywords->where('type', 'level')->values();
        $this->tags = $keywords->where('type', '!=', 'level')->values();
        return $this;
    }

    public function rankByKeywords()
    {
        // Preserve musical similarity and eligibility from the existing engine,
        // but randomize only equal best fits, never lower-scoring runners-up.
        $ranked = $this->similar->map(function ($piece) {
            return ['piece' => $piece, 'score' => $piece->tags->intersect($this->tags)->count()];
        });
        $this->ranking = $ranked->where('score', $ranked->max('score'))->pluck('piece');
    }

    public function findSimilar($withVideoAndScore = false, $onlyFreePicks = false)
    {
        $moods = $this->pieces->flatMap(function ($piece) { return $piece->mood()->pluck('id'); })->unique();
        $level = MatchTour::baseLevel($this->preferredLevel());
        $query = Piece::whereNotIn('pieces.id', $this->exclude['pieces'])
            ->whereNotIn('composer_id', $this->exclude['composers'])
            ->whereHas('tags', function ($q) use ($moods) { $q->whereIn('tags.id', $moods); })
            ->whereHas('tags', function ($q) use ($level) {
                $q->where('type', 'level')->whereIn('name', [$level, 'early '.$level, 'late '.$level]);
            })
            ->select('pieces.*')->with(['tags', 'composer' => function ($q) { $q->select('composers.*')->setEagerLoads([]); }]);
        if ($withVideoAndScore) $query->withVideoAndScore();
        if ($onlyFreePicks) $query->freePicks(false);

        // Listening measures taste, not ability. Use the verified playing/reading
        // range above, never the arbitrary distance between database tag IDs.
        $this->similar = $query->get();
    }
}
